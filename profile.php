<?php include 'includes/header.php'; ?>

<h2 class="mb-4"><i class="bi bi-building"></i> Profil <?= DESA_NAMA ?></h2>

<div class="row">
    <div class="col-md-8">
        <!-- Sejarah -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Sejarah Desa</h5>
            </div>
            <div class="card-body">
                <p><?= DESA_NAMA ?> didirikan pada tahun 1976 oleh sekelompok pendatang dari berbagai daerah di Jawa Barat. Awalnya, wilayah ini merupakan area pertanian yang subur dengan hamparan sawah dan kebun yang luas. Seiring berjalannya waktu, desa ini berkembang menjadi permukiman yang terdiri dari lima dusun, yaitu Sukamaju, Suka Damai, Cirendeu, Mekarjaya, dan Pasirsari.</p>
                <p>Nama "Sukamaju" sendiri diambil dari harapan para pendiri desa agar desa ini selalu dalam keadaan suka (senang) dan maju dalam segala aspek kehidupan. Hingga saat ini, desa terus berkembang dengan berbagai pembangunan infrastruktur dan peningkatan kualitas sumber daya manusia.</p>
            </div>
        </div>

        <!-- Visi Misi -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-bullseye"></i> Visi dan Misi</h5>
            </div>
            <div class="card-body">
                <h5>Visi</h5>
                <p>"Mewujudkan <?= DESA_NAMA ?> yang maju, mandiri, dan sejahtera berdasarkan nilai-nilai gotong royong dan kearifan lokal."</p>
                <h5>Misi</h5>
                <ol>
                    <li>Meningkatkan kualitas pelayanan publik yang transparan dan akuntabel</li>
                    <li>Mengembangkan potensi ekonomi desa melalui pemberdayaan UMKM</li>
                    <li>Meningkatkan infrastruktur desa yang berkelanjutan</li>
                    <li>Memperkuat ketahanan sosial melalui kegiatan kemasyarakatan</li>
                    <li>Meningkatkan kualitas pendidikan dan kesehatan warga</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Struktur Organisasi -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Struktur Organisasi</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td><strong>Kepala Desa</strong></td>
                            <td><?= KEPALA_DESA ?></td>
                        </tr>
                        <tr>
                            <td><strong>Sekretaris</strong></td>
                            <td>Dra. Hj. Mimin</td>
                        </tr>
                        <tr>
                            <td><strong>Kaur Umum</strong></td>
                            <td>Asep Saepudin, S.Sos</td>
                        </tr>
                        <tr>
                            <td><strong>Kaur Keuangan</strong></td>
                            <td>Neni Suhaeni, S.E</td>
                        </tr>
                        <tr>
                            <td><strong>Kaur Perencanaan</strong></td>
                            <td>Dedi Mulyadi, S.T</td>
                        </tr>
                        <tr>
                            <td><strong>Kadus Sukamaju</strong></td>
                            <td>Ujang Suryana</td>
                        </tr>
                        <tr>
                            <td><strong>Kadus Suka Damai</strong></td>
                            <td>Ahmad Hidayat</td>
                        </tr>
                        <tr>
                            <td><strong>Kadus Cirendeu</strong></td>
                            <td>Eko Prasetyo</td>
                        </tr>
                        <tr>
                            <td><strong>Kadus Mekarjaya</strong></td>
                            <td>Gunawan</td>
                        </tr>
                        <tr>
                            <td><strong>Kadus Pasirsari</strong></td>
                            <td>Irfan Hakim</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Data Wilayah -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Data Wilayah</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><strong>Provinsi:</strong> <?= DESA_PROVINSI ?></li>
                    <li><strong>Kabupaten:</strong> <?= DESA_KABUPATEN ?></li>
                    <li><strong>Kecamatan:</strong> <?= DESA_KECAMATAN ?></li>
                    <li><strong>Desa:</strong> <?= DESA_NAMA ?></li>
                    <li><strong>Jumlah Dusun:</strong> 5</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
