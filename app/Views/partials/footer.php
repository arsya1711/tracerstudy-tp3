<?php
/*
|-------------------------------------------------------------------
| PARTIAL FOOTER
|-------------------------------------------------------------------
| Penjelasan fungsi kode ini: partial ini menutup area konten utama,
| menampilkan footer Metronic, memuat semua JavaScript dashboard,
| lalu menyediakan slot extra_js untuk script tambahan per halaman.
| Alur kerja: layouts/main memanggil partial ini setelah section
| content selesai dirender, lalu browser memuat script global dan
| vendor yang dibutuhkan dashboard.
|
| Tips Debugging:
| - Jika script dashboard tidak jalan, periksa path asset JS di partial ini.
| - Jika JavaScript tambahan halaman tidak masuk, periksa section extra_js pada view turunan.
*/
?>
							</div>
							<!--end::Content container-->
						</div>
						<!--end::Content-->
					</div>
					<!--end::Content wrapper-->
					<!--begin::Footer-->
					<div id="kt_app_footer" class="app-footer">
						<!--begin::Footer container-->
						<div
							class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
							<!--begin::Copyright-->
							<div class="text-dark order-2 order-md-1">
								<span class="text-muted fw-semibold me-1">2023&copy;</span>
								<a href="https://keenthemes.com" target="_blank"
									class="text-gray-800 text-hover-primary">Keenthemes</a>
							</div>
							<!--end::Copyright-->
							<!--begin::Menu-->
							<ul class="menu menu-gray-600 menu-hover-primary fw-semibold order-1">
								<li class="menu-item">
									<a href="https://keenthemes.com" target="_blank" class="menu-link px-2">About</a>
								</li>
								<li class="menu-item">
									<a href="https://devs.keenthemes.com" target="_blank"
										class="menu-link px-2">Support</a>
								</li>
								<li class="menu-item">
									<a href="https://1.envato.market/EA4JP" target="_blank"
										class="menu-link px-2">Purchase</a>
								</li>
							</ul>
							<!--end::Menu-->
						</div>
						<!--end::Footer container-->
					</div>
					<!--end::Footer-->
				</div>
				<!--end:::Main-->
			</div>
			<!--end::Wrapper-->
		</div>
		<!--end::Page-->
	</div>
	<!--end::App-->

	<!--begin::Scrolltop-->
	<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
		<i class="ki-duotone ki-arrow-up">
			<span class="path1"></span>
			<span class="path2"></span>
		</i>
	</div>
	<!--end::Scrolltop-->

	<!--begin::Javascript-->
	<script>var hostUrl = "<?= base_url('assets/') ?>";</script>
	<script src="<?= base_url('assets/plugins/global/plugins.bundle.js') ?>"></script>
	<script src="<?= base_url('assets/js/scripts.bundle.js') ?>"></script>
	<script src="<?= base_url('assets/plugins/custom/datatables/datatables.bundle.js') ?>"></script>
	<script src="<?= base_url('assets/js/widgets.bundle.js') ?>"></script>
	<script src="<?= base_url('assets/js/custom/widgets.js') ?>"></script>
	<script src="<?= base_url('assets/js/custom/apps/chat/chat.js') ?>"></script>
	<?php
	/*
	|-------------------------------------------------------------------
	| TOASTR JAVASCRIPT DASHBOARD
	|-------------------------------------------------------------------
	| Penjelasan fungsi kode ini: memuat library Toastr dan mengubah
	| flashdata session dari controller menjadi notifikasi kecil di sisi
	| kanan atas dashboard.
	| Alur kerja: setelah semua script global Metronic dimuat, browser
	| memuat Toastr lalu membaca flashdata sukses, error, info, dan
	| warning untuk menampilkan notifikasi yang sesuai.
	|
	| Tips Debugging:
	| - Jika notifikasi tidak muncul, periksa flashdata session masih tersedia pada request dashboard.
	| - Jika muncul error "toastr is not defined", periksa file toastr.min.js gagal termuat.
	*/
	?>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			if (typeof toastr === 'undefined') {
				return;
			}

			toastr.options = {
				'closeButton': true,
				'debug': false,
				'newestOnTop': true,
				'progressBar': true,
				'positionClass': 'toast-top-right',
				'preventDuplicates': true,
				'onclick': null,
				'showDuration': '300',
				'hideDuration': '1000',
				'timeOut': '3500',
				'extendedTimeOut': '1000',
				'showEasing': 'swing',
				'hideEasing': 'linear',
				'showMethod': 'fadeIn',
				'hideMethod': 'fadeOut'
			};

			<?php if (session()->getFlashdata('sukses')): ?>
				toastr.success('<?= esc(session()->getFlashdata('sukses'), 'js') ?>', 'Berhasil');
			<?php endif; ?>

			<?php if (session()->getFlashdata('error')): ?>
				toastr.error('<?= esc(session()->getFlashdata('error'), 'js') ?>', 'Gagal');
			<?php endif; ?>

			<?php if (session()->getFlashdata('info')): ?>
				toastr.info('<?= esc(session()->getFlashdata('info'), 'js') ?>', 'Info');
			<?php endif; ?>

			<?php if (session()->getFlashdata('warning')): ?>
				toastr.warning('<?= esc(session()->getFlashdata('warning'), 'js') ?>', 'Peringatan');
			<?php endif; ?>
		});
	</script>
	<?php
	/*
	|-------------------------------------------------------------------
	| SWEETALERT2 KONFIRMASI LOGOUT
	|-------------------------------------------------------------------
	| Penjelasan fungsi kode ini: menambahkan popup konfirmasi sebelum
	| proses logout dashboard dijalankan agar pengguna tidak keluar
	| secara tidak sengaja saat masih bekerja.
	| Alur kerja: script mencari semua link dengan class
	| js-logout-trigger, menghentikan klik default, lalu menampilkan
	| dialog SweetAlert2. Jika pengguna menekan tombol konfirmasi,
	| browser diarahkan ke URL logout.
	|
	| Tips Debugging:
	| - Jika popup tidak muncul saat klik logout, periksa SweetAlert2 berhasil termuat dan class js-logout-trigger ada pada link.
	| - Jika setelah konfirmasi tidak pindah ke halaman login, periksa data-logout-url pada link logout.
	*/
	?>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			var logoutLinks = document.querySelectorAll('.js-logout-trigger');

			logoutLinks.forEach(function (logoutLink) {
				logoutLink.addEventListener('click', function (event) {
					event.preventDefault();

					var logoutUrl = this.getAttribute('data-logout-url') || this.getAttribute('href');

					if (typeof Swal === 'undefined') {
						window.location.href = logoutUrl;
						return;
					}

					Swal.fire({
						icon: 'warning',
						title: 'Apakah Anda yakin ingin keluar?',
						text: 'Pastikan semua data sudah tersimpan.',
						showCancelButton: true,
						confirmButtonText: 'Ya, Logout',
						cancelButtonText: 'Batal',
						reverseButtons: true
					}).then(function (result) {
						if (result.isConfirmed) {
							window.location.href = logoutUrl;
						}
					});
				});
			});
		});
	</script>
	<?= $this->renderSection('extra_js') ?>
	<!--end::Javascript-->
</body>
<!--end::Body-->
</html>
