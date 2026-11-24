<?php

namespace Database\Seeders;

use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\Role;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Commodity;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\FarmPlot;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\HarvestPlan;
use App\Modules\ProcurementReceiving\Models\Partner;
use App\Modules\ProcurementReceiving\Models\PriceListItem;
use App\Modules\ProcurementReceiving\Models\StandardPackage;
use App\Modules\ProcurementReceiving\Models\UomConversion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // DS-LOK
        $lok = [];
        foreach ([['G1', 'Gudang 1', 'GUDANG'], ['G2', 'Gudang 2', 'GUDANG'], ['G3', 'Gudang 3', 'GUDANG'], ['C1', 'Cabang 1', 'CABANG'], ['C2', 'Cabang 2', 'CABANG']] as [$kode, $nama, $tipe]) {
            $lok[$kode] = Location::create(['kode' => $kode, 'nama' => $nama, 'tipe' => $tipe, 'aktif' => true]);
        }

        $peran = [];
        foreach (['PEMILIK' => 'Pemilik', 'ADMIN' => 'Admin Operasional', 'PENGADAAN' => 'Staf Pengadaan', 'PETUGAS_GUDANG' => 'Petugas Gudang', 'KEPALA_GUDANG' => 'Kepala Gudang', 'KURIR' => 'Kurir', 'KASIR' => 'Kasir', 'KEUANGAN' => 'Keuangan', 'PETANI' => 'Petani Mitra', 'MITRA_B2B' => 'Retail B2B'] as $kode => $nama) {
            $peran[$kode] = Role::create(['kode' => $kode, 'nama' => $nama]);
        }

        $sand = Hash::make('Sileuit#2026');
        $buatStaf = function (string $kode, string $nama, string $role, array $lokasi) use ($peran, $sand, $lok) {
            $u = User::create(['name' => $nama, 'email' => $kode.'@sileuit.id', 'password' => $sand, 'status' => 'AKTIF']);
            $u->roles()->attach($peran[$role]);
            $ids = collect($lokasi)->map(fn ($k) => $lok[$k]->id)->all();
            $u->locationScopes()->attach($ids);
            if ($ids) {
                $u->update(['active_location_id' => $ids[0]]);
            }

            return $u;
        };

        // DS-USR
        $pemilik = $buatStaf('pemilik-1', 'Pemilik Contoh', 'PEMILIK', []);
        $buatStaf('adm-1', 'Admin Contoh', 'ADMIN', []);
        $buatStaf('pengadaan-1', 'Pengadaan Contoh', 'PENGADAAN', []);
        $buatStaf('keu-1', 'Keuangan Contoh', 'KEUANGAN', []);
        $buatStaf('pg-g1', 'Petugas Gudang G1', 'PETUGAS_GUDANG', ['G1']);
        $buatStaf('pg-g2', 'Petugas Gudang G2', 'PETUGAS_GUDANG', ['G2']);
        $buatStaf('kg-g1', 'Kepala Gudang G1', 'KEPALA_GUDANG', ['G1']);
        $buatStaf('kg-g2', 'Kepala Gudang G2', 'KEPALA_GUDANG', ['G2']);
        $buatStaf('ksr-c1', 'Kasir Cabang 1', 'KASIR', ['C1']);
        $buatStaf('ksr-c2', 'Kasir Cabang 2', 'KASIR', ['C2']);
        $buatStaf('kur-1', 'Kurir Contoh', 'KURIR', ['G1']);

        // DS-TANI & DS-MITRA
        $p001 = Partner::create(['tipe' => 'FARMER', 'nama' => 'Petani Contoh A', 'hp' => '+628111111001', 'kelompok' => 'Tani Makmur', 'status' => 'AKTIF']);
        $p002 = Partner::create(['tipe' => 'FARMER', 'nama' => 'Petani Contoh B', 'hp' => '+628111111002', 'kelompok' => 'Tani Sejahtera', 'status' => 'AKTIF']);
        FarmPlot::create(['partner_id' => $p001->id, 'nama_desa' => 'Sukamaju', 'luas_ha' => 0.5]);
        FarmPlot::create(['partner_id' => $p002->id, 'nama_desa' => 'Cibogo', 'luas_ha' => 0.75]);
        $r01 = Partner::create(['tipe' => 'RETAIL', 'nama' => 'Retail Contoh Satu', 'hp' => '+628222222001', 'batas_kredit' => 15000000, 'termin_hari' => 14, 'status' => 'AKTIF']);
        $r02 = Partner::create(['tipe' => 'RETAIL', 'nama' => 'Retail Contoh Dua', 'hp' => '+628222222002', 'batas_kredit' => 10000000, 'termin_hari' => 7, 'status' => 'AKTIF']);

        $pin = Hash::make('123456');
        foreach ([[$p001, '+628111111001', 'PETANI'], [$p002, '+628111111002', 'PETANI'], [$r01, '+628222222001', 'MITRA_B2B'], [$r02, '+628222222002', 'MITRA_B2B']] as [$partner, $hp, $role]) {
            $u = User::create([
                'name' => $partner->nama,
                'email' => $role === 'MITRA_B2B' ? strtolower(str_replace(' ', '-', $partner->nama)).'@mitra.sileuit.id' : null,
                'hp' => $hp,
                'password' => $role === 'MITRA_B2B' ? $sand : null,
                'pin_hash' => $role === 'PETANI' ? $pin : null,
                'status' => 'AKTIF',
                'partner_id' => $partner->id,
            ]);
            $u->roles()->attach($peran[$role]);
        }

        $gabah = Commodity::create(['nama' => 'GABAH', 'satuan_dasar' => 'kg', 'umur_simpan_hari' => 180]);
        $beras = Commodity::create(['nama' => 'BERAS', 'satuan_dasar' => 'kg', 'umur_simpan_hari' => 180]);
        $grade = [];
        foreach ([$gabah, $beras] as $kom) {
            foreach (['A' => 14, 'B' => 16, 'C' => 18] as $kode => $maks) {
                $grade[$kom->nama][$kode] = Grade::create(['commodity_id' => $kom->id, 'kode' => $kode, 'ambang_mutu' => ['kadar_air_max' => $maks]]);
            }
        }

        foreach ([['KG', 1], ['KARUNG_5', 5], ['KARUNG_10', 10], ['KARUNG_25', 25], ['KARUNG_50', 50]] as [$nama, $faktor]) {
            UomConversion::create(['nama' => $nama, 'faktor_ke_kg' => $faktor]);
        }

        $paket = StandardPackage::create(['nama' => 'Paket Standar Gabah Jabar 2026', 'jenis_pupuk' => 'Berimbang', 'jendela_tanam' => 'Nov–Mar', 'parameter_mutu' => ['kadar_air_max' => 14, 'hampa_max' => 5]]);

        $k1 = Contract::create(['partner_id' => $p001->id, 'standard_package_id' => $paket->id, 'musim' => '2026/2027', 'mulai' => '2026-11-01', 'selesai' => '2027-03-31', 'estimasi_kg' => 5000, 'status' => 'AKTIF']);
        $k2 = Contract::create(['partner_id' => $p002->id, 'standard_package_id' => $paket->id, 'musim' => '2026/2027', 'mulai' => '2026-11-01', 'selesai' => '2027-03-31', 'estimasi_kg' => 3000, 'status' => 'AKTIF']);
        HarvestPlan::create(['contract_id' => $k1->id, 'tgl_perkiraan' => '2026-11-12', 'kg_perkiraan' => 1000, 'status' => 'RENCANA']);
        HarvestPlan::create(['contract_id' => $k2->id, 'tgl_perkiraan' => '2026-11-19', 'kg_perkiraan' => 800, 'status' => 'RENCANA']);

        // DS-HARGA (disetujui Pemilik, berlaku 1 Nov 2026)
        foreach (['A' => 6800, 'B' => 6500, 'C' => 6200] as $kode => $harga) {
            PriceListItem::create([
                'commodity_id' => $gabah->id,
                'grade_id' => $grade['GABAH'][$kode]->id,
                'harga_per_kg' => $harga,
                'berlaku_dari' => '2026-11-01',
                'disetujui_oleh' => $pemilik->id,
                'disetujui_pada' => now(),
            ]);
        }
    }
}
