# pertemuan-01**1. Kesinambungan PWD-DPW-DPWL**

PWD,DPW,dan DPWL memiliki hubungan yang berkesinambungan dalam pengembangan aplikasi.

- **PWD** (Pemrograman Web Dasar) merupakan tahap awal untuk memahami dasar-dasar pemrograman web, seperti HTML ,CSS ,PHP ,form ,dan pengolahan data.
- **DPW** (Desain dan Pemrograman Web) merupakan pengembangan materi PWD. Pada tahap ini, konsep pemrograman web dikembangkan menjadi aplikasi yang lebih terstruktur dan terorganisasi, termasuk penggunaan database dan pola arsitektur seperti MVC.
- **DPWL** (Desain dan Pemrograman Web Lanjut) melanjutkan kemampuan dari PWD dan DPW dengan membangun aplikasi web yang lebih kompleks, terstruktur, dan memiliki fitur yang lebih lengkap.

Dengan demikian, hubungan ketiganya dapat digambarkan sebagai:

**PWD -> DPW -> DPWL**

Artinya,pemahaman dari PWD menjadi dasar untuk mempelajari DPW, sedangkan kemampuan yang diperoleh pada DPW menjadi dasar untuk mempelajari DPWL.

**2. Perbedaan PHP Terstruktur dan MVC**

**PHP** terstruktur adalah cara membuat aplikasi PHP dengan menuliskan logika program secara langsung dan berurutan dalam satu atau beberapa file. Pada pendekatan ini, kode untuk menerima input, mengolah data, berhubungan dengan database, dan menampilkan halaman dapat berada dalam file yang sama.

Sedangkan **MVC** (Model-View-Controller) merupakan pola arsitektur yang memisahkan aplikasi menjadi tiga bagian utama yaitu Model, View, Controller. 

**3. Fungsi Model, View, dan Controller**

**a. Model**

**Model** bertanggung jawab terhadap data dan logika yang berkaitan dengan data.

- Mengambil data dari database
- Menambahkan data baru
- Mengubah data
- Menghapus data
- Melakukan validasi atau pengolahan data tertentu

**b. View**

**View** bertanggung jawab terhadap tampilan yang dilihat oleh pengguna.

contoh tugas View:

- Menampilkan halaman HTML
- Menampilkan data yang dikirim oleh Controller
- Menyediakan form input
- Menampilkan tabel, tombol, menu, dan elemen antarmuka lainnya

view sebaiknya tidak menangani proses utama pengolahan data.

**c. Controller**

**Controller** berfungsi sebagai penghubung antara pengguna, model, dan view.

Contoh tugas Controller:

- Menerima request dari pengguna
- Menentukan proses yang harus dijalankan
- Memanggil model untuk mengambil atau mengolah data
- Mengirim hasil pengolahan data ke view
- Mementukan view yang akan ditampilkan kepada pengguna. 

Secara sederhana: 

**Controller menerima request -> Controler memproses -> Model mengolah data -> Controller -> View menampilkan hasil.**

**4. Alur Request-Response pada MVC**

**Alur** request-response pada **MVC** dapat dijelaskan sebagai berikut:

1. User mengirim request melalui browser, misalnya dengan membuka halaman /mahasiswa.
2. Request diterima oleh aplikasi dan diarahkan ke controller yang sesuai
3. Controller memproses request dan menentukan tindakan yang diperlukan
4. Jika membutuhkan data, Controller memanggil model
5. Model mengambil atau mengolah data, misalnya mengambil data mahasiswa dari database
6. Hasil dari model dikembalikan kepada controller
7. Controller mengirimkan data tersebut kepada view
8. View membuat tampilan HTML berdasarkan data yang diterima
9. HTML dikirim kembali sebagai response kepada browser
10. Browser menampilkan halaman kepada pengguna.

**5. Pemetaan Fitur Aplikasi DPW ke Model, Controller, dan View**

Sebagai contoh, digunakan fitur Data Mahasiswa yang memiliki fungsi untuk menampilkan daftar mahasiswa, menambahkan data mahasiswa, mengubah data, dan menghapus data

**a. Model**

Contoh:

MahasiswaModel

Tanggung jawabnya adalah mengelola data mahasiswa, misalnya:

getAll()

getByid()

create()

update()

delete()

Alasan:operasi tersebut berhubungan langsung dengan pengelolaan data mahasiswa dan database sehingga ditempatkan pada **Model**.

**b. Controller**

Contoh:

MahasiswaController

Tanggung jawabnya mengatur proses ketika pengguna mengakses fitur mahasiswa, misalnya:

index( )         -> menampilkan daftar mahasiswa

create( )        -> menampilkan form tambah mahasiswa

store( )         -> menyimpan mahasiswa baru 

edit( )          -> menampilkan form edit 

update( )        -> memperbarui data mahasiswa 

delete( )        -> menghapus data mahasiswa

**Alasan**: Controller bertugas menerima request dari pengguna, memanggil fungsi pada model, kemudian menentukan View yang harus ditampilkan.

**c. View**

Contoh:

mahasiswa/index.php

mahasiswa/create.php

mahasiswa/edit.php

**View** -> **index.php** dapat digunakan untuk menampilkan daftar mahasiswa, sedangkan **create.php** digunakan untuk form penambahan mahasiswa dan **edit.php** untuk form perubahan data.

**Alasan** : file-file tersebut berhubungan dengan antarmuka yang dilihat pengguna sehingga termasuk bagian view.

**6. Kesimpulan P1**

Pada pertemuan pertama, dapat disimpulkan bahwa pengembangan aplikasi web dilakukan secara bertahap dari **PWD**, kemudian **DPW**, dan dilanjutkan ke **DPWL**. **PWD** memberikan dasar pemrograman web, **DPW** mengembangkan dasar tersebut menjadi aplikasi yang lebih terstruktur, sedangkan **DPWL** melanjutkan pengembangan ke aplikasi yang lebih kompleks.

**PHP** terstruktur dan **MVC** memiliki perbedaan terutama pada perorganisasian kode. MVC memisahkan aplikasi menjadi **Model, View, dan Controller** sehingga kode lebih terorganisai dan lebih mudah dipelihara.

Dalam **MVC**, **Model** menangani data, **View** menangani tampilan, dan **Controller** mengatur ulur request serta menghubungkan **model** dengan **View**. Dengan pembagian tersebut, fitur aplikasi **DPW** seperti pengelolaan data mahasiswa dapat dipetakan secara jelas ke masing-masing bagian **MVC**.

Dengan memahami konsep tersebut, pengembangan aplikasi web menjadi lebih terstruktur karena setiap komponen memiliki tanggung jawab yang berbeda dan saling bekerja sama dalam menghasilkan response kepada pengguna.
