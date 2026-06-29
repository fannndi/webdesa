    </main>
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><?= defined('DESA_NAMA') ? DESA_NAMA : 'Desa' ?></h5>
                    <p><?= defined('DESA_KECAMATAN') ? DESA_KECAMATAN : '' ?><br>
                       <?= defined('DESA_KABUPATEN') ? DESA_KABUPATEN : '' ?><br>
                       <?= defined('DESA_PROVINSI') ? DESA_PROVINSI : '' ?></p>
                </div>
                <div class="col-md-4">
                    <h5>Navigasi Cepat</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>" class="text-white">Beranda</a></li>
                        <li><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>profile.php" class="text-white">Profil Desa</a></li>
                        <li><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>berita.php" class="text-white">Berita</a></li>
                        <li><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>cek_warga.php" class="text-white">Cek NIK</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Kontak</h5>
                    <p><i class="bi bi-telephone"></i> (021) 1234567<br>
                       <i class="bi bi-envelope"></i> info@desasukamaju.id<br>
                       <i class="bi bi-geo-alt"></i> Jl. Raya Sukamaju No. 1</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; 2026 <?= defined('DESA_NAMA') ? DESA_NAMA : 'Desa' ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>assets/js/main.js"></script>
</body>
</html>
