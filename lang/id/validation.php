<?php

return [
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'max' => [
        'string' => ':attribute maksimal :max karakter.',
        'file' => 'Ukuran :attribute maksimal :max KB.',
        'numeric' => ':attribute maksimal :max.',
    ],
    'min' => [
        'string' => ':attribute minimal :min karakter.',
        'numeric' => ':attribute minimal :min.',
    ],
    'integer' => ':attribute harus berupa angka bulat.',
    'numeric' => ':attribute harus berupa angka.',
    'date' => ':attribute bukan tanggal yang valid.',
    'after_or_equal' => ':attribute tidak boleh sebelum :date.',
    'in' => ':attribute yang dipilih tidak valid.',
    'file' => ':attribute harus berupa berkas.',
    'mimes' => ':attribute harus berformat: :values.',
    'uploaded' => ':attribute gagal diunggah. Pastikan ukuran maksimal 2 MB.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'accepted' => ':attribute harus disetujui.',
    'regex' => 'Format :attribute tidak valid.',
    'current_password' => 'Kata sandi salah.',
    'attributes' => [
        'nomor_induk' => 'NIM/NIDN/NIP',
        'password' => 'Kata sandi',
        'nama' => 'Nama',
        'email' => 'Email',
        'berkas' => 'Berkas',
        'alasan' => 'Alasan',
        'catatan' => 'Catatan',
    ],
];
