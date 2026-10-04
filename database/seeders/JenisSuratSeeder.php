<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\KlasifikasiSurat;
use Illuminate\Database\Seeder;

/**
 * Lima jenis surat mahasiswa. Token di template: {{ pemohon.* }}, {{ isian.* }},
 * {{ penandatangan.jabatan }}, {{ tanggal }} — dirender oleh App\Services\PenyusunSurat.
 */
class JenisSuratSeeder extends Seeder
{
    private const TABEL_PEMOHON = <<<'HTML'
<table class="data">
  <tr><td width="32%">Nama</td><td width="3%">:</td><td><strong>{{ pemohon.nama }}</strong></td></tr>
  <tr><td>NIM</td><td>:</td><td>{{ pemohon.nim }}</td></tr>
  <tr><td>Program Studi</td><td>:</td><td>{{ pemohon.prodi }}</td></tr>
  <tr><td>Tempat, Tanggal Lahir</td><td>:</td><td>{{ pemohon.ttl }}</td></tr>
</table>
HTML;

    public function run(): void
    {
        $dekan = Jabatan::where('kode', 'dekan')->value('id');
        $klas = KlasifikasiSurat::pluck('id', 'kode');
        $ktm = ['label' => 'Kartu Tanda Mahasiswa (KTM) Aktif', 'wajib' => true];
        $spp = ['label' => 'Bukti Pembayaran SPP Semester Berjalan', 'wajib' => true];

        $daftar = [
            [
                'kode' => 'KET-AKTIF', 'nama' => 'Surat Keterangan Aktif Kuliah', 'ikon' => 'school', 'kategori' => 'Akademik',
                'deskripsi' => 'Untuk BPJS, beasiswa, atau tunjangan orang tua.', 'klasifikasi' => 'II.1.AK',
                'verifikator_role' => 'admin_tu', 'perlu_paraf' => false, 'paraf_role' => null, 'sla_hari' => 2,
                'field_formulir' => [
                    ['nama' => 'keperluan', 'label' => 'Keperluan', 'tipe' => 'pilihan', 'wajib' => true,
                        'opsi' => ['Pengurusan BPJS', 'Beasiswa / KIP Kuliah', 'Tunjangan orang tua', 'Lainnya']],
                    ['nama' => 'semester', 'label' => 'Semester', 'tipe' => 'angka', 'wajib' => true, 'placeholder' => 'Contoh: 7'],
                    ['nama' => 'tahun_akademik', 'label' => 'Tahun Akademik', 'tipe' => 'teks', 'wajib' => true, 'placeholder' => 'Contoh: 2025/2026 Ganjil'],
                    ['nama' => 'keterangan', 'label' => 'Keterangan Tambahan', 'tipe' => 'area', 'wajib' => false, 'maks' => 250],
                ],
                'syarat' => [$ktm, $spp],
                'template_html' => <<<'HTML'
<p>Yang bertanda tangan di bawah ini, {{ penandatangan.jabatan }} Universitas Muhammadiyah Buton, menerangkan bahwa:</p>
HTML.self::TABEL_PEMOHON.<<<'HTML'
<p>adalah benar mahasiswa yang terdaftar dan aktif mengikuti perkuliahan pada Semester {{ isian.semester }} Tahun Akademik {{ isian.tahun_akademik }} di Fakultas Teknik Universitas Muhammadiyah Buton, serta tidak sedang menjalani sanksi akademik apapun.</p>
<p>Surat keterangan ini diterbitkan untuk keperluan <strong>{{ isian.keperluan }}</strong>.</p>
<p>Demikian surat keterangan ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
HTML,
            ],
            [
                'kode' => 'IZIN-PENELITIAN', 'nama' => 'Surat Izin Penelitian', 'ikon' => 'science', 'kategori' => 'Penelitian',
                'deskripsi' => 'Keperluan skripsi, tugas akhir, dan riset.', 'klasifikasi' => 'II.4.PN',
                'verifikator_role' => 'admin_tu', 'perlu_paraf' => true, 'paraf_role' => 'wakil_dekan', 'sla_hari' => 3,
                'field_formulir' => [
                    ['nama' => 'judul', 'label' => 'Judul Penelitian / Skripsi', 'tipe' => 'area', 'wajib' => true, 'maks' => 250,
                        'placeholder' => 'Contoh: Rancang Bangun Sistem Monitoring Kualitas Air Berbasis IoT pada PDAM Kota Baubau'],
                    ['nama' => 'kepada', 'label' => 'Ditujukan Kepada (Nama Jabatan / Pejabat)', 'tipe' => 'teks', 'wajib' => true,
                        'placeholder' => 'Contoh: Kepala Dinas Komunikasi dan Informatika Kota Baubau'],
                    ['nama' => 'instansi', 'label' => 'Instansi / Perusahaan Tujuan', 'tipe' => 'teks', 'wajib' => true,
                        'placeholder' => 'Contoh: Dinas Komunikasi dan Informatika (Diskominfo) Kota Baubau'],
                    ['nama' => 'alamat_instansi', 'label' => 'Alamat Lengkap Instansi Tujuan', 'tipe' => 'teks', 'wajib' => true,
                        'placeholder' => 'Contoh: Jl. Palagimata No. 12, Kel. Lipu, Kec. Betoambari, Kota Baubau'],
                    ['nama' => 'tgl_mulai', 'label' => 'Tanggal Mulai Penelitian', 'tipe' => 'tanggal', 'wajib' => true, 'lebar' => 'setengah'],
                    ['nama' => 'tgl_selesai', 'label' => 'Tanggal Selesai Penelitian', 'tipe' => 'tanggal', 'wajib' => true, 'lebar' => 'setengah'],
                    ['nama' => 'pembimbing', 'label' => 'Dosen Pembimbing Utama / Pembimbing Skripsi', 'tipe' => 'teks', 'wajib' => true],
                ],
                'syarat' => [$ktm, $spp, ['label' => 'Lembar Pengesahan Proposal Tugas Akhir', 'wajib' => false]],
                'template_html' => <<<'HTML'
<table class="data">
  <tr><td width="14%">Perihal</td><td width="3%">:</td><td>Permohonan Izin Penelitian</td></tr>
</table>
<p>Yth. {{ isian.kepada }}<br>{{ isian.instansi }}<br>di {{ isian.alamat_instansi }}</p>
<p><em>Assalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>
<p>Dengan hormat, kami sampaikan bahwa mahasiswa Fakultas Teknik Universitas Muhammadiyah Buton:</p>
HTML.self::TABEL_PEMOHON.<<<'HTML'
<p>bermaksud melaksanakan penelitian dalam rangka penyusunan tugas akhir dengan judul <strong>"{{ isian.judul }}"</strong> di bawah bimbingan {{ isian.pembimbing }}, yang direncanakan pada tanggal {{ isian.tgl_mulai }} sampai dengan {{ isian.tgl_selesai }}.</p>
<p>Sehubungan dengan hal tersebut, kami mohon kiranya Bapak/Ibu berkenan memberikan izin dan bantuan kepada mahasiswa yang bersangkutan untuk melaksanakan penelitian di instansi yang Bapak/Ibu pimpin.</p>
<p>Demikian permohonan ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>
<p><em>Wassalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>
HTML,
            ],
            [
                'kode' => 'PENGANTAR-KP', 'nama' => 'Surat Pengantar Kerja Praktik', 'ikon' => 'apartment', 'kategori' => 'Kerja Praktik',
                'deskripsi' => 'Permohonan magang / KP di instansi.', 'klasifikasi' => 'II.5.KP',
                'verifikator_role' => 'kaprodi', 'perlu_paraf' => false, 'paraf_role' => null, 'sla_hari' => 3,
                'field_formulir' => [
                    ['nama' => 'kepada', 'label' => 'Ditujukan Kepada (Jabatan / Pejabat)', 'tipe' => 'teks', 'wajib' => true],
                    ['nama' => 'instansi', 'label' => 'Nama Instansi / Perusahaan', 'tipe' => 'teks', 'wajib' => true],
                    ['nama' => 'alamat_instansi', 'label' => 'Alamat Instansi', 'tipe' => 'teks', 'wajib' => true],
                    ['nama' => 'tgl_mulai', 'label' => 'Rencana Mulai KP', 'tipe' => 'tanggal', 'wajib' => true, 'lebar' => 'setengah'],
                    ['nama' => 'tgl_selesai', 'label' => 'Rencana Selesai KP', 'tipe' => 'tanggal', 'wajib' => true, 'lebar' => 'setengah'],
                    ['nama' => 'anggota', 'label' => 'Anggota Kelompok (bila ada)', 'tipe' => 'area', 'wajib' => false, 'maks' => 250,
                        'placeholder' => 'Satu baris per orang: Nama – NIM'],
                ],
                'syarat' => [$ktm, ['label' => 'Kartu Rencana Studi (KRS) Semester Berjalan', 'wajib' => true]],
                'template_html' => <<<'HTML'
<table class="data">
  <tr><td width="14%">Perihal</td><td width="3%">:</td><td>Permohonan Kerja Praktik</td></tr>
</table>
<p>Yth. {{ isian.kepada }}<br>{{ isian.instansi }}<br>di {{ isian.alamat_instansi }}</p>
<p><em>Assalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>
<p>Dalam rangka pelaksanaan kurikulum Fakultas Teknik Universitas Muhammadiyah Buton, kami mohon kesediaan Bapak/Ibu untuk menerima mahasiswa berikut sebagai peserta Kerja Praktik (KP) di instansi yang Bapak/Ibu pimpin:</p>
HTML.self::TABEL_PEMOHON.<<<'HTML'
<p style="white-space:pre-line;text-align:left;margin-left:18px">{{ isian.anggota }}</p>
<p>Kerja praktik direncanakan pada tanggal {{ isian.tgl_mulai }} sampai dengan {{ isian.tgl_selesai }}.</p>
<p>Demikian permohonan ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>
<p><em>Wassalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>
HTML,
            ],
            [
                'kode' => 'CUTI-AKADEMIK', 'nama' => 'Surat Cuti Akademik', 'ikon' => 'pause_circle', 'kategori' => 'Akademik',
                'deskripsi' => 'Permohonan istirahat studi semester resmi.', 'klasifikasi' => 'II.1.AK',
                'verifikator_role' => 'kaprodi', 'perlu_paraf' => true, 'paraf_role' => 'wakil_dekan', 'sla_hari' => 5,
                'field_formulir' => [
                    ['nama' => 'semester_cuti', 'label' => 'Semester yang Dicutikan', 'tipe' => 'teks', 'wajib' => true, 'placeholder' => 'Contoh: Ganjil 2026/2027'],
                    ['nama' => 'lama', 'label' => 'Lama Cuti', 'tipe' => 'pilihan', 'wajib' => true, 'opsi' => ['1 (satu) semester', '2 (dua) semester']],
                    ['nama' => 'alasan', 'label' => 'Alasan Cuti', 'tipe' => 'area', 'wajib' => true, 'maks' => 250],
                ],
                'syarat' => [$ktm, ['label' => 'Bukti Lunas Administrasi Keuangan', 'wajib' => true], ['label' => 'Surat Pernyataan Orang Tua/Wali', 'wajib' => false]],
                'template_html' => <<<'HTML'
<p>Yang bertanda tangan di bawah ini, {{ penandatangan.jabatan }} Universitas Muhammadiyah Buton, memberikan persetujuan cuti akademik kepada:</p>
HTML.self::TABEL_PEMOHON.<<<'HTML'
<p>untuk <strong>{{ isian.lama }}</strong> pada Semester {{ isian.semester_cuti }} dengan alasan: {{ isian.alasan }}.</p>
<p>Selama masa cuti, mahasiswa yang bersangkutan tidak mengikuti kegiatan akademik dan masa cuti tidak dihitung sebagai masa studi. Mahasiswa wajib melapor dan mendaftar ulang pada semester berikutnya sesuai ketentuan yang berlaku.</p>
<p>Demikian surat ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
HTML,
            ],
            [
                'kode' => 'REKOM-BEASISWA', 'nama' => 'Surat Rekomendasi Beasiswa', 'ikon' => 'workspace_premium', 'kategori' => 'Kemahasiswaan',
                'deskripsi' => 'Surat keterangan & rekomendasi Dekanat.', 'klasifikasi' => 'II.2.KM',
                'verifikator_role' => 'admin_tu', 'perlu_paraf' => true, 'paraf_role' => 'wakil_dekan', 'sla_hari' => 3,
                'field_formulir' => [
                    ['nama' => 'nama_beasiswa', 'label' => 'Nama Beasiswa', 'tipe' => 'teks', 'wajib' => true],
                    ['nama' => 'penyelenggara', 'label' => 'Penyelenggara Beasiswa', 'tipe' => 'teks', 'wajib' => true],
                    ['nama' => 'semester', 'label' => 'Semester Saat Ini', 'tipe' => 'angka', 'wajib' => true, 'lebar' => 'setengah'],
                    ['nama' => 'ipk', 'label' => 'IPK Terakhir', 'tipe' => 'teks', 'wajib' => true, 'lebar' => 'setengah', 'placeholder' => 'Contoh: 3,65'],
                    ['nama' => 'prestasi', 'label' => 'Prestasi / Alasan Mengajukan (opsional)', 'tipe' => 'area', 'wajib' => false, 'maks' => 250],
                ],
                'syarat' => [$ktm, ['label' => 'Transkrip Nilai / KHS Terakhir', 'wajib' => true]],
                'template_html' => <<<'HTML'
<p>Yang bertanda tangan di bawah ini, {{ penandatangan.jabatan }} Universitas Muhammadiyah Buton, dengan ini memberikan rekomendasi kepada:</p>
HTML.self::TABEL_PEMOHON.<<<'HTML'
<p>Mahasiswa tersebut saat ini duduk di Semester {{ isian.semester }} dengan Indeks Prestasi Kumulatif (IPK) {{ isian.ipk }}, berkelakuan baik, dan tidak sedang menjalani sanksi akademik maupun disiplin.</p>
<p>Berdasarkan hal tersebut, kami merekomendasikan yang bersangkutan untuk mengikuti seleksi <strong>{{ isian.nama_beasiswa }}</strong> yang diselenggarakan oleh {{ isian.penyelenggara }}.</p>
<p>Demikian surat rekomendasi ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
HTML,
            ],

            // ---- Format untuk staf (dibuat lewat Surat Keluar → Buat Surat) -----------------------------
            [
                'kode' => 'UNDANGAN-RAPAT', 'sasaran' => 'staf', 'nama' => 'Surat Undangan Rapat', 'ikon' => 'groups', 'kategori' => 'Umum',
                'deskripsi' => 'Mengundang dosen, tendik, atau pihak lain ke rapat/kegiatan.', 'klasifikasi' => 'II.3.AU',
                'perihal_template' => 'Undangan {{ isian.nama_rapat }}', 'judul_surat' => null,
                'verifikator_role' => 'admin_tu', 'perlu_paraf' => false, 'paraf_role' => null, 'sla_hari' => 1, 'syarat' => [],
                'field_formulir' => [
                    ['nama' => 'kepada', 'label' => 'Tujuan surat (Yth.)', 'tipe' => 'area', 'wajib' => true, 'maks' => 500, 'placeholder' => "Ketua Program Studi\nFakultas Teknik UM Buton\ndi Tempat"],
                    ['nama' => 'lampiran', 'label' => 'Lampiran', 'tipe' => 'teks', 'wajib' => false, 'placeholder' => 'Contoh: 1 (satu) berkas', 'lebar' => 'setengah'],
                    ['nama' => 'nama_rapat', 'label' => 'Nama rapat / kegiatan', 'tipe' => 'teks', 'wajib' => true, 'placeholder' => 'Contoh: Rapat Koordinasi Penjaminan Mutu', 'lebar' => 'setengah'],
                    ['nama' => 'hari_tanggal', 'label' => 'Hari / tanggal', 'tipe' => 'tanggal', 'wajib' => true, 'lebar' => 'setengah'],
                    ['nama' => 'waktu', 'label' => 'Waktu', 'tipe' => 'teks', 'wajib' => true, 'placeholder' => '09.00 WITA – selesai', 'lebar' => 'setengah'],
                    ['nama' => 'tempat', 'label' => 'Tempat', 'tipe' => 'teks', 'wajib' => true, 'placeholder' => 'Ruang Rapat Dekanat'],
                    ['nama' => 'agenda', 'label' => 'Agenda', 'tipe' => 'area', 'wajib' => false, 'maks' => 500],
                ],
                'template_html' => <<<'HTML'
<table class="data" style="margin-left:0;width:100%"><tr><td width="14%">Lampiran</td><td width="3%">:</td><td>{{ isian.lampiran }}</td></tr>
<tr><td>Perihal</td><td>:</td><td><strong>Undangan {{ isian.nama_rapat }}</strong></td></tr></table>
<p>Yth. {{ isian.kepada }}</p>
<p><em>Assalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>
<p>Dengan hormat, kami mengundang Bapak/Ibu untuk menghadiri <strong>{{ isian.nama_rapat }}</strong> yang akan dilaksanakan pada:</p>
<table class="data">
  <tr><td width="26%">Hari/Tanggal</td><td width="3%">:</td><td>{{ isian.hari_tanggal }}</td></tr>
  <tr><td>Waktu</td><td>:</td><td>{{ isian.waktu }}</td></tr>
  <tr><td>Tempat</td><td>:</td><td>{{ isian.tempat }}</td></tr>
  <tr><td>Agenda</td><td>:</td><td>{{ isian.agenda }}</td></tr>
</table>
<p>Demikian undangan ini disampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.</p>
<p><em>Wassalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>
HTML,
            ],
            [
                'kode' => 'SURAT-TUGAS', 'sasaran' => 'staf', 'nama' => 'Surat Tugas', 'ikon' => 'assignment_ind', 'kategori' => 'Kepegawaian',
                'deskripsi' => 'Penugasan dosen/tendik untuk suatu kegiatan.', 'klasifikasi' => 'II.6.SK',
                'perihal_template' => 'Surat Tugas {{ isian.nama_penerima }}', 'judul_surat' => 'SURAT TUGAS',
                'verifikator_role' => 'admin_tu', 'perlu_paraf' => false, 'paraf_role' => null, 'sla_hari' => 1, 'syarat' => [],
                'field_formulir' => [
                    ['nama' => 'nama_penerima', 'label' => 'Nama yang ditugaskan', 'tipe' => 'teks', 'wajib' => true, 'lebar' => 'setengah'],
                    ['nama' => 'nomor_induk', 'label' => 'NIDN / NIP', 'tipe' => 'teks', 'wajib' => false, 'lebar' => 'setengah'],
                    ['nama' => 'jabatan_penerima', 'label' => 'Jabatan', 'tipe' => 'teks', 'wajib' => false, 'placeholder' => 'Contoh: Dosen Program Studi Teknik Sipil'],
                    ['nama' => 'uraian_tugas', 'label' => 'Uraian tugas', 'tipe' => 'area', 'wajib' => true, 'maks' => 600, 'placeholder' => 'Contoh: mengikuti pelatihan penyusunan instrumen akreditasi'],
                    ['nama' => 'tempat', 'label' => 'Tempat pelaksanaan', 'tipe' => 'teks', 'wajib' => true],
                    ['nama' => 'tgl_mulai', 'label' => 'Tanggal mulai', 'tipe' => 'tanggal', 'wajib' => true, 'lebar' => 'setengah'],
                    ['nama' => 'tgl_selesai', 'label' => 'Tanggal selesai', 'tipe' => 'tanggal', 'wajib' => true, 'lebar' => 'setengah'],
                ],
                'template_html' => <<<'HTML'
<p>Yang bertanda tangan di bawah ini, {{ penandatangan.jabatan }} Universitas Muhammadiyah Buton, dengan ini menugaskan kepada:</p>
<table class="data">
  <tr><td width="32%">Nama</td><td width="3%">:</td><td><strong>{{ isian.nama_penerima }}</strong></td></tr>
  <tr><td>NIDN / NIP</td><td>:</td><td>{{ isian.nomor_induk }}</td></tr>
  <tr><td>Jabatan</td><td>:</td><td>{{ isian.jabatan_penerima }}</td></tr>
</table>
<p>untuk {{ isian.uraian_tugas }} bertempat di {{ isian.tempat }}, pada tanggal {{ isian.tgl_mulai }} sampai dengan {{ isian.tgl_selesai }}.</p>
<p>Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab dan melaporkan hasilnya kepada pimpinan.</p>
HTML,
            ],
            [
                'kode' => 'SURAT-PEMBERITAHUAN', 'sasaran' => 'staf', 'nama' => 'Surat Pemberitahuan (Tanpa QR)', 'ikon' => 'mail', 'kategori' => 'Umum',
                'deskripsi' => 'Surat biasa yang dicetak, ditandatangani basah, dan dicap.', 'klasifikasi' => 'II.3.AU', 'mode_ttd' => 'basah',
                'perihal_template' => '{{ isian.hal }}', 'judul_surat' => null,
                'verifikator_role' => 'admin_tu', 'perlu_paraf' => false, 'paraf_role' => null, 'sla_hari' => 1, 'syarat' => [],
                'field_formulir' => [
                    ['nama' => 'kepada', 'label' => 'Tujuan surat (Yth.)', 'tipe' => 'area', 'wajib' => true, 'maks' => 500],
                    ['nama' => 'hal', 'label' => 'Hal / perihal', 'tipe' => 'teks', 'wajib' => true],
                    ['nama' => 'isi', 'label' => 'Isi surat', 'tipe' => 'area', 'wajib' => true, 'maks' => 3000],
                ],
                'template_html' => <<<'HTML'
<table class="data" style="margin-left:0;width:100%"><tr><td width="14%">Perihal</td><td width="3%">:</td><td><strong>{{ isian.hal }}</strong></td></tr></table>
<p>Yth. {{ isian.kepada }}</p>
<p><em>Assalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>
<p>{{ isian.isi }}</p>
<p>Demikian disampaikan, atas perhatian Bapak/Ibu diucapkan terima kasih.</p>
<p><em>Wassalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>
HTML,
            ],

            // ---- Format surat MASUK (dicatat TU lewat Surat Masuk → Catat Surat) -------------------------
            [
                'kode' => 'SM-UMUM', 'sasaran' => 'masuk', 'nama' => 'Surat Masuk Umum', 'ikon' => 'move_to_inbox', 'kategori' => 'Surat Masuk',
                'deskripsi' => 'Surat yang diterima fakultas (kolom standar saja).', 'klasifikasi' => 'II.3.AU', 'judul_surat' => null, 'penandatangan_jabatan_id' => null,
                'verifikator_role' => 'admin_tu', 'perlu_paraf' => false, 'paraf_role' => null, 'sla_hari' => 1, 'syarat' => [], 'field_formulir' => [], 'template_html' => '',
            ],
            [
                'kode' => 'SM-UNDANGAN', 'sasaran' => 'masuk', 'nama' => 'Undangan (Surat Masuk)', 'ikon' => 'event', 'kategori' => 'Surat Masuk',
                'deskripsi' => 'Undangan rapat/kegiatan dari pihak luar; mencatat waktu dan tempat kegiatan.', 'klasifikasi' => 'II.3.AU', 'judul_surat' => null, 'penandatangan_jabatan_id' => null,
                'verifikator_role' => 'admin_tu', 'perlu_paraf' => false, 'paraf_role' => null, 'sla_hari' => 1, 'syarat' => [], 'template_html' => '',
                'field_formulir' => [
                    ['nama' => 'tanggal_kegiatan', 'label' => 'Tanggal kegiatan', 'tipe' => 'tanggal', 'wajib' => true, 'lebar' => 'setengah'],
                    ['nama' => 'waktu', 'label' => 'Waktu kegiatan', 'tipe' => 'teks', 'wajib' => false, 'placeholder' => '08.30 WITA – selesai', 'lebar' => 'setengah'],
                    ['nama' => 'tempat', 'label' => 'Tempat kegiatan', 'tipe' => 'teks', 'wajib' => false],
                ],
            ],
            [
                'kode' => 'SM-PERMOHONAN', 'sasaran' => 'masuk', 'nama' => 'Permohonan / Audiensi (Surat Masuk)', 'ikon' => 'mark_email_unread', 'kategori' => 'Surat Masuk',
                'deskripsi' => 'Permohonan data, kerja sama, atau audiensi dari instansi lain.', 'klasifikasi' => 'II.3.AU', 'judul_surat' => null, 'penandatangan_jabatan_id' => null,
                'verifikator_role' => 'admin_tu', 'perlu_paraf' => false, 'paraf_role' => null, 'sla_hari' => 1, 'syarat' => [], 'template_html' => '',
                'field_formulir' => [
                    ['nama' => 'bentuk_permohonan', 'label' => 'Bentuk permohonan', 'tipe' => 'pilihan', 'wajib' => true, 'opsi' => ['Permohonan data', 'Kerja sama', 'Audiensi', 'Bantuan / dukungan', 'Lainnya'], 'lebar' => 'setengah'],
                    ['nama' => 'batas_tanggapan', 'label' => 'Batas waktu tanggapan', 'tipe' => 'tanggal', 'wajib' => false, 'lebar' => 'setengah'],
                ],
            ],
        ];

        foreach ($daftar as $i => $d) {
            $kl = $d['klasifikasi'];
            unset($d['klasifikasi']);
            $d += ['sasaran' => 'mahasiswa', 'mode_ttd' => 'qr', 'perihal_template' => null];
            if (! array_key_exists('judul_surat', $d)) {
                $d['judul_surat'] = mb_strtoupper($d['nama']);
            }
            JenisSurat::updateOrCreate(['kode' => $d['kode']], $d + [
                'klasifikasi_id' => $klas[$kl],
                'penandatangan_jabatan_id' => $dekan,
                'urutan' => $i + 1,
            ]);
        }
    }
}
