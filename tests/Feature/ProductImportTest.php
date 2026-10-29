<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use ZipArchive;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('productos.csv', "\xEF\xBB\xBF".$content);
    }

    /**
     * Excel mínimo de una hoja, armado a mano (un .xlsx es un zip con XML).
     *
     * @param  list<list<string|int|float>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $strings = [];
        $sheetRows = '';

        foreach ($rows as $r => $row) {
            $cells = '';
            foreach ($row as $c => $value) {
                $ref = chr(65 + $c).($r + 1);
                if (is_string($value)) {
                    $strings[] = htmlspecialchars($value);
                    $cells .= "<c r=\"{$ref}\" t=\"s\"><v>".(count($strings) - 1).'</v></c>';
                } else {
                    $cells .= "<c r=\"{$ref}\"><v>{$value}</v></c>";
                }
            }
            $sheetRows .= '<row r="'.($r + 1)."\">{$cells}</row>";
        }

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .implode('', array_map(fn ($s) => "<si><t>{$s}</t></si>", $strings)).'</sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            .$sheetRows.'</sheetData></worksheet>');
        $zip->close();

        return new UploadedFile($path, 'productos.xlsx', null, null, true);
    }

    private function preview(UploadedFile $file): TestResponse
    {
        return $this->post(route('erp.products.import.preview'), ['file' => $file]);
    }

    private function confirm(TestResponse $preview): TestResponse
    {
        return $this->post(route('erp.products.import.store'), ['token' => $preview->viewData('token')]);
    }

    public function test_csv_is_previewed_and_then_imported(): void
    {
        $preview = $this->preview($this->csv(
            "Nombre;Categoría;Costo;Precio;IVA;Stock;Código de barras\n".
            "Mate de cerámica;Cocina;3000;$ 6.050;21;12;\n".
            "Taza grande;Cocina;1.500,50;;;3;\n",
        ));

        $preview->assertOk()->assertSee('Mate de cerámica')->assertSee('Falta el precio');
        $this->assertSame(0, Product::count(), 'Revisar no guarda nada.');
        $this->confirm($preview)->assertRedirect(route('erp.products.import'));
        $this->assertSame(0, Product::count(), 'Con errores no se importa nada.');
    }

    public function test_valid_sheet_creates_products_categories_and_initial_stock(): void
    {
        $preview = $this->preview($this->csv(
            "nombre;categoria;sku;costo;margen;precio;stock\n".
            "Mate de cerámica;Cocina;;3000;;6050;12\n".
            "Set de vasos x6;Cocina;VAS-006;8000;50;;5\n",
        ));
        $preview->assertOk()->assertSee('Nueva');

        $this->confirm($preview)->assertRedirect(route('erp.products.index'))->assertSessionHas('success');

        $mate = Product::where('name', 'Mate de cerámica')->sole();
        $this->assertSame('DB-00001', $mate->sku, 'Sin SKU en la planilla se genera uno.');
        $this->assertNull($mate->barcode);
        $this->assertEquals(5000, $mate->sale_price, '6050 con IVA = 5000 sin IVA.');
        $this->assertEquals(6050, $mate->final_price);
        $this->assertEquals(21, $mate->tax_rate);
        $this->assertSame(12, $mate->stock);
        $this->assertSame(StockMovementType::Initial, $mate->stockMovements()->sole()->type);

        $vasos = Product::where('sku', 'VAS-006')->sole();
        $this->assertEquals(12000, $vasos->sale_price, 'Margen 50% sobre costo 8000.');

        $this->assertSame(1, Category::where('name', 'Cocina')->count());
        $this->assertTrue($mate->category->is($vasos->category));
    }

    public function test_xlsx_files_are_read(): void
    {
        $preview = $this->preview($this->xlsx([
            ['Nombre', 'Costo', 'Precio venta', 'IVA', 'Stock'],
            ['Florero de vidrio', 2000, 3500.5, 0.21, 4],   // IVA con formato % en Excel llega como 0.21
        ]));

        $preview->assertOk()->assertSee('Florero de vidrio');
        $this->confirm($preview)->assertRedirect(route('erp.products.index'));

        $product = Product::sole();
        $this->assertEquals(3500.5, $product->sale_price);
        $this->assertEquals(21, $product->tax_rate);
        $this->assertSame(4, $product->stock);
    }

    public function test_existing_products_are_not_imported_again(): void
    {
        Product::factory()->create(['name' => 'Mate de cerámica', 'sku' => 'MAT-1']);

        $preview = $this->preview($this->csv(
            "nombre;sku;costo;precio\n".
            "Mate de cerámica;;3000;6000\n".
            "Otro mate;MAT-1;3000;6000\n",
        ));

        $preview->assertOk()->assertSee('Ya existe un producto con este nombre.')->assertSee('El SKU MAT-1 ya está usado.');
        $this->assertSame(2, $preview->viewData('errorCount'));
    }

    public function test_only_spreadsheets_are_accepted(): void
    {
        $this->preview(UploadedFile::fake()->create('foto.jpg', 10))->assertSessionHasErrors('file');
    }
}
