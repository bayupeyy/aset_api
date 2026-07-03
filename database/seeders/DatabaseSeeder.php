<?php
namespace Database\Seeders;
use App\Models\{User, Division, AssetCategory, Vendor, Location, Asset, Barcode, AssetStatusLog};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Divisi
        $divIT  = Division::create(['name' => 'IT', 'code' => 'IT']);
        $divFin = Division::create(['name' => 'Finance', 'code' => 'FIN']);
        $divHR  = Division::create(['name' => 'HR', 'code' => 'HR']);
        $divMkt = Division::create(['name' => 'Marketing', 'code' => 'MKT']);

        // Users
        $admin = User::create([
            'name' => 'Admin IT', 'email' => 'admin@assetit.local',
            'password' => Hash::make('password'), 'role' => 'admin',
            'employee_id' => 'EMP001', 'division_id' => $divIT->id, 'is_active' => true,
        ]);
        User::create([
            'name' => 'Budi Santoso', 'email' => 'budi@assetit.local',
            'password' => Hash::make('password'), 'role' => 'staff',
            'employee_id' => 'EMP002', 'division_id' => $divIT->id, 'is_active' => true,
        ]);
        $rina = User::create([
            'name' => 'Rina Kusuma', 'email' => 'rina@assetit.local',
            'password' => Hash::make('password'), 'role' => 'staff',
            'employee_id' => 'EMP003', 'division_id' => $divFin->id, 'is_active' => true,
        ]);

        // Kategori
        $laptop  = AssetCategory::create(['name' => 'Laptop',  'code_prefix' => 'LPT', 'icon' => 'laptop']);
        $monitor = AssetCategory::create(['name' => 'Monitor', 'code_prefix' => 'MON', 'icon' => 'monitor']);
        $printer = AssetCategory::create(['name' => 'Printer', 'code_prefix' => 'PRN', 'icon' => 'printer']);
        $server  = AssetCategory::create(['name' => 'Server',  'code_prefix' => 'SRV', 'icon' => 'server']);

        // Vendor
        $vendor = Vendor::create(['name' => 'PT Maju Jaya Teknologi', 'contact_person' => 'Agus', 'phone' => '021-555-1234', 'email' => 'sales@majujaya.co.id']);

        // Lokasi
        $lt3IT  = Location::create(['building' => 'Gedung A', 'floor' => 'Lantai 3', 'room' => 'Ruang IT']);
        $lt5Fin = Location::create(['building' => 'Gedung A', 'floor' => 'Lantai 5', 'room' => 'Ruang Finance']);
        $lt2HR  = Location::create(['building' => 'Gedung B', 'floor' => 'Lantai 2', 'room' => 'Ruang HR']);

        // Contoh aset
        $assets = [
            ['name'=>'Dell Latitude 5540','brand'=>'Dell','model'=>'Latitude 5540','serial_number'=>'DL554-2024-001','category_id'=>$laptop->id,'purchase_price'=>18500000,'purchase_date'=>'2024-01-15','warranty_until'=>'2027-01-15','location_id'=>$lt5Fin->id,'current_user_id'=>$rina->id,'vendor_id'=>$vendor->id],
            ['name'=>'Lenovo ThinkPad X1','brand'=>'Lenovo','model'=>'ThinkPad X1','serial_number'=>'LNV-X1-2024-001','category_id'=>$laptop->id,'purchase_price'=>22000000,'purchase_date'=>'2024-02-01','warranty_until'=>'2027-02-01','location_id'=>$lt3IT->id,'current_user_id'=>null,'vendor_id'=>$vendor->id],
            ['name'=>'HP LaserJet M404','brand'=>'HP','model'=>'LaserJet M404dn','serial_number'=>'HP-M404-2024-001','category_id'=>$printer->id,'purchase_price'=>5500000,'purchase_date'=>'2024-03-01','warranty_until'=>'2026-03-01','location_id'=>$lt3IT->id,'current_user_id'=>null,'vendor_id'=>$vendor->id,'status'=>'maintenance'],
            ['name'=>'LG UltraWide 34"','brand'=>'LG','model'=>'34WL500','serial_number'=>'LG-34WL-2024-001','category_id'=>$monitor->id,'purchase_price'=>8500000,'purchase_date'=>'2024-01-20','warranty_until'=>'2027-01-20','location_id'=>$lt2HR->id,'current_user_id'=>null,'vendor_id'=>$vendor->id],
        ];

        foreach ($assets as $data) {
            $status = $data['status'] ?? 'active';
            unset($data['status']);
            $asset = Asset::create(array_merge($data, [
                'asset_code' => Asset::generateCode(AssetCategory::find($data['category_id'])->code_prefix),
                'status'     => $status,
            ]));
            Barcode::create([
                'asset_id'      => $asset->id,
                'barcode_value' => $asset->asset_code,
                'qr_value'      => 'http://asset-api.test/api/scan/' . $asset->asset_code,
                'is_active'     => true,
                'generated_by'  => $admin->id,
            ]);
            AssetStatusLog::create([
                'asset_id'   => $asset->id,
                'old_status' => '',
                'new_status' => $status,
                'notes'      => 'Aset didaftarkan',
                'changed_by' => $admin->id,
                'changed_at' => now(),
            ]);
        }

        $this->command->info('Seeder selesai! Login: admin@assetit.local / password');
    }
}
