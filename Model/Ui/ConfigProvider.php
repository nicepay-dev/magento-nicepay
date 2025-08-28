<?php

namespace Nicepay\NicePayment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Nicepay\NicePayment\Gateway\Config\Config;
use Nicepay\NicePayment\Model\Payment\Nicepay;
use Nicepay\NicePayment\Helper\Data as NicepayHelper;


class ConfigProvider implements ConfigProviderInterface
{

	private $nicepay;

	/**
	 * @var NicepayHelper
	 */
	private $nicepayHelper;


	public function __construct(
		Nicepay $nicepay,
		NicepayHelper $nicepayHelper,

	) {
		$this->nicepay = $nicepay;
		$this->nicepayHelper = $nicepayHelper;
	}



	public function getConfig()
	{

		return [
			'payment' => [
				Config::CODE => [
					'title' => $this->nicepayHelper->getPaymentTitle("nicepay"),
				],
				'card' => [
					'title' => $this->nicepayHelper->getPaymentTitle("card"),
					'description' => $this->nicepayHelper->getPaymentDescription("card"),
				],
				'virtual_account' => [
					'title' => $this->nicepayHelper->getPaymentTitle("virtual_account"),
					'description' => $this->nicepayHelper->getPaymentDescription("virtual_account"),
					'additionalInfo' => $this->activeBankList(),
				],
				'cvs' => [
					'title' => $this->nicepayHelper->getPaymentTitle("cvs"),
					'description' => $this->nicepayHelper->getPaymentDescription("cvs"),
					'additionalInfo' => $this->activeMitraList('cvs'),
				],
				'qris' => [
					'title' => $this->nicepayHelper->getPaymentTitle("qris"),
					'description' => $this->nicepayHelper->getPaymentDescription("qris"),
				],
				'ewallet' => [
					'title' => $this->nicepayHelper->getPaymentTitle("ewallet"),
					'description' => $this->nicepayHelper->getPaymentDescription("ewallet"),
					'additionalInfo' => $this->activeMitraList('ewallet'),

				],
				'payloan' => [
					'title' => $this->nicepayHelper->getPaymentTitle("payloan"),
					'description' => $this->nicepayHelper->getPaymentDescription("payloan"),
					'additionalInfo' => $this->activeMitraList('payloan'),

				],
				'payout' => [
					'title' => $this->nicepayHelper->getPaymentTitle("payout"),
					'description' => $this->nicepayHelper->getPaymentDescription("payout"),
					'additionalInfo' => $this->payoutBankList(),
				],
				'redirect' => [
					'title' => $this->nicepayHelper->getPaymentTitle("redirect"),
					'description' => $this->nicepayHelper->getPaymentDescription("redirect"),
				],

			]
		];
	}

	public static function convenienceStoreList($mitraCd = null)
	{
		$mitra = [
			'ALMA' => [
				'label' => __('ALFA GROUP'),
				'content' => '<div class="cvs-instructions">
                <h4>ALFA GROUP</h4>
                <h5>Panduan Bayar</h5>
                <ol>
                    <li>Pilih pembayaran melalui Alfamart/ Alfamidi/ Dan+Dan/ Lawson</li>
                    <li>Catat atau print kode pembayaran</li>
                    <li>Bawa kode pembayaran tersebut ke gerai Alfamart / Alfamidi / Dan+Dan / Lawson</li>
                    <li>Informasikan kepada kasir pembayaran menggunakan NICEPay + Nama Merchant</li>
                    <li>Berikan kode pembayaran ke kasir</li>
                    <li>Kasir akan memasukkan kode pembayaran</li>
                    <li>Bayar sesuai nominal</li>
                    <li>Ambil tanda terima pembayaran</li>
                    <li>Selesai</li>
                </ol>
                <small>*Minimum pembayaran menggunakan Convenience Store adalah Rp 10.000</small>
            </div>'
			],
			'INDO' => [
				'label' => __('INDOMARET'),
				'content' => '<div class="cvs-instructions">
                <h4>INDOMARET</h4>
                <h5>Panduan Bayar</h5>
                <ol>
                    <li>Pilih pembayaran melalui INDOMARET</li>
                    <li>Catat atau print kode pembayaran</li>
                    <li>Bawa kode pembayaran tersebut ke gerai INDOMARET</li>
                    <li>Informasikan Nama Merchant ke kasir</li>
                    <li>Berikan kode pembayaran ke kasir</li>
                    <li>Kasir akan memasukkan kode pembayaran</li>
                    <li>Bayar sesuai nominal</li>
                    <li>Ambil tanda terima pembayaran</li>
                    <li>Selesai</li>
                </ol>
                <small>*Minimum pembayaran menggunakan Convenience Store adalah Rp 10.000</small>
            </div>'
			]
		];

		if ($mitraCd !== null) {
			return $mitra[$mitraCd] ?? null;
		}

		return $mitra;
	}

	public static function payloanMitraList($mitraCd = null)
	{
		$mitra = [
			'AKLP' => [
				'label' => __('AKULAKU'),
				'content' => '<strong>AKULAKU Payment Steps</strong>
            <div style="border:1px solid #cccccc;padding:10px 20px 0;margin-bottom:15px;">
            <ul style="list-style-type: disc; padding-left:20px;">
                <li>Pilih Pembayaran melalui Akulaku</li>
                <li>Anda akan dipindahkan ke Halaman Pembayaran Akulaku</li>
                <li>Masuk menggunakan akun dan OTP</li>
                <li>Pastikan Credit Limit anda cukup</li>
                <li>Lanjut dan Konfirmasi</li>
                <li>Pembayaranmu sudah Berhasil! Anda akan dipindahkan ke result page</li>
            </ul>
            </div>
            <small>*Pastikan aplikasi Akulaku sudah terinstall di perangkat Anda</small>'
			],
			'KDVI' => [
				'label' => __('KREDIVO'),
				'content' => '<strong>KREDIVO Payment Steps</strong>
            <div style="border:1px solid #cccccc;padding:10px 20px 0;margin-bottom:15px;">
            <ul style="list-style-type: disc; padding-left:20px;">
                <li>Pilih Pembayaran melalui Kredivo</li>
                <li>Anda akan dipindahkan ke Halaman Pembayaran Kredivo</li>
                <li>Masuk menggunakan akun dan OTP</li>
                <li>Pastikan Credit Limit anda cukup</li>
                <li>Lanjut dan Konfirmasi</li>
                <li>Pembayaranmu sudah Berhasil! Anda akan dipindahkan ke result page</li>
            </ul>
            </div>
            <small>*Tenor pembayaran akan ditampilkan di aplikasi Kredivo</small>'
			],
			'IDNA' => [
				'label' => __('INDODANA'),
				'content' => '<strong>INDODANA Payment Steps</strong>
            <div style="border:1px solid #cccccc;padding:10px 20px 0;margin-bottom:15px;">
            <ul style="list-style-type: disc; padding-left:20px;">
                <li>Pengguna memilih pembayaran dengan Indodana</li>
                <li>Pengguna akan diarahkan ke halaman Indodana</li>
                <li>Masukkan nomor handphone</li>
                <li>Masukkan PIN</li>
                <li>Tekan tombol Bayar pada halaman rincian transaksi</li>
                <li>Tekan tombol Saya Setuju pada pop-up Perjanjian Peminjaman</li>
                <li>Masukkan PIN</li>
                <li>Muncul halaman transaksi berhasil</li>
                <li>Tekan tombol Kembali ke Merchant untuk kembali ke halaman NICEPAY</li>
            </ul>
            </div>
            <small>*Pastikan akun Indodana Paylater Anda aktif</small>'
			]
		];

		if ($mitraCd !== null) {
			return $mitra[$mitraCd] ?? null;
		}

		return $mitra;
	}

	public static function generalPaymentGuide($payMethod, $code = null)
	{

		$paymentGuide = [
			'01' => self::generalCCGuide(),
			'02' => self::bankList($code),
			'03' => self::convenienceStoreList($code),
			'04' => self::generalDirectDebitGuide(),
			'05' => self::ewalletMitraList($code),
			'06' => self::payloanMitraList($code),
			'07' => self::payoutBankList($code),
			'08' => self::generalQrisGuide(),
			'09' => self::generalGPNGuide(),
			'00' => self::generalRedirectGuide()
		];

		if ($payMethod !== null) {
			return $paymentGuide[$payMethod] ?? null;
		}

		return $paymentGuide;
	}

	public static function paymentMethodList($code = null)
	{
		$methodMap = [
			'01' => 'Credit Card',
			'02' => 'Virtual Account',
			'03' => 'Convenience Store',
			'04' => 'Direct Debit',
			'05' => 'E-Wallet',
			'06' => 'Paylater',
			'07' => 'Payout',
			'08' => 'QRIS',
			'09' => 'GPN Card',
			'00' => 'Nicepay Payment Page'
		];

		if ($code !== null) {
			return $methodMap[$code] ?? null;
		}

		return $methodMap;
	}

	public static function generalRedirectGuide()
	{

		return [
			'label' => __('Nicepay Payment Page'),
			'content' =>  '<div style="border:1px solid #e0e0e0; padding:20px; border-radius:8px; background:#f9f9f9;">
							<h3 style="margin-top:0; color:#1979c3;">Proses Pembayaran Melalui Halaman Nicepay</h3>
							
							<div style="display:flex; margin-bottom:15px;">
								<div style="background:#1979c3; color:white; border-radius:50%; width:24px; height:24px; text-align:center; line-height:24px; margin-right:10px;">1</div>
								<div style="flex:1;">
									<strong>Anda akan diarahkan ke halaman pembayaran Nicepay</strong>
									<p style="margin:5px 0 0; color:#555; font-size:14px;">Sistem sedang mempersiapkan halaman pembayaran...</p>
								</div>
							</div>
							
							<div style="display:flex; margin-bottom:15px;">
								<div style="background:#1979c3; color:white; border-radius:50%; width:24px; height:24px; text-align:center; line-height:24px; margin-right:10px;">2</div>
								<div style="flex:1;">
									<strong>Pilih metode pembayaran</strong>
									<p style="margin:5px 0 0; color:#555; font-size:14px;">(Transfer Bank, Kartu Kredit, E-Wallet, dll)</p>
								</div>
							</div>
							
							<div style="display:flex; margin-bottom:15px;">
								<div style="background:#1979c3; color:white; border-radius:50%; width:24px; height:24px; text-align:center; line-height:24px; margin-right:10px;">3</div>
								<div style="flex:1;">
									<strong>Lengkapi data pembayaran</strong>
									<p style="margin:5px 0 0; color:#555; font-size:14px;">Isi informasi yang diperlukan sesuai metode pembayaran</p>
								</div>
							</div>
							
							<div style="display:flex; margin-bottom:15px;">
								<div style="background:#1979c3; color:white; border-radius:50%; width:24px; height:24px; text-align:center; line-height:24px; margin-right:10px;">4</div>
								<div style="flex:1;">
									<strong>Selesaikan pembayaran</strong>
									<p style="margin:5px 0 0; color:#555; font-size:14px;">Ikuti petunjuk terakhir di halaman pembayaran</p>
								</div>
							</div>
							
							<div style="display:flex;">
								<div style="background:#4caf50; color:white; border-radius:50%; width:24px; height:24px; text-align:center; line-height:24px; margin-right:10px;">✓</div>
								<div style="flex:1;">
									<strong>Pembayaran berhasil!</strong>
									<p style="margin:5px 0 0; color:#555; font-size:14px;">Anda akan kembali otomatis ke halaman konfirmasi merchant</p>
								</div>
							</div>
							
							<div style="margin-top:20px; padding:12px; background:#e8f5e9; border-left:4px solid #4caf50; border-radius:0 4px 4px 0;">
								<strong style="color:#2e7d32;">Perhatian:</strong>
								<ul style="margin:8px 0 0 20px; padding-left:0; color:#555; font-size:13px;">
									<li>Jangan tutup browser selama proses pembayaran</li>
									<li>Proses otomatis memakan waktu 5-10 detik setelah pembayaran</li>
									<li>Hubungi merchant jika tidak kembali otomatis</li>
								</ul>
							</div>
						</div>'
		];
	}


	public static function generalGPNGuide()
	{

		return [
			'label' => __('GPN'),
			'content' =>  '<strong>GPN Payment Steps</strong>
							<div style="border:1px solid #e0e0e0; padding:12px 20px 5px; margin-bottom:15px; border-radius:6px; background:#fafafa;">
							<ul style="list-style-type: none; padding-left:0; margin:0;">
								<li style="padding:8px 0; border-bottom:1px dashed #eee; display:flex;">
									<span style="color:#1979c3; font-weight:bold; min-width:25px;">1</span>
									Pilih Pembayaran melalui <strong>GPN</strong>
								</li>
								<li style="padding:8px 0; border-bottom:1px dashed #eee; display:flex;">
									<span style="color:#1979c3; font-weight:bold; min-width:25px;">2</span>
									Pilih bank penerbit kartu GPN Anda
								</li>
								<li style="padding:8px 0; border-bottom:1px dashed #eee; display:flex; flex-wrap:wrap;">
									<span style="color:#1979c3; font-weight:bold; min-width:25px;">3</span>
									<div>
										Masukkan detail kartu:
										<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:8px; margin:8px 0 0 25px; padding:10px; background:#fff; border-radius:4px; border:1px solid #eee;">
											<div>
												<div style="font-size:11px; color:#666;">Nomor Kartu</div>
												<div style="height:32px; background:#f5f5f5; border-radius:3px;"></div>
											</div>
											<div>
												<div style="font-size:11px; color:#666;">MM/YY</div>
												<div style="height:32px; background:#f5f5f5; border-radius:3px;"></div>
											</div>
											<div>
												<div style="font-size:11px; color:#666;">CVV</div>
												<div style="height:32px; background:#f5f5f5; border-radius:3px;"></div>
											</div>
										</div>
									</div>
								</li>
								<li style="padding:8px 0; border-bottom:1px dashed #eee; display:flex;">
									<span style="color:#1979c3; font-weight:bold; min-width:25px;">4</span>
									Klik <strong style="color:#1979c3;">Konfirmasi</strong>
								</li>
								<li style="padding:8px 0; border-bottom:1px dashed #eee; display:flex;">
									<span style="color:#1979c3; font-weight:bold; min-width:25px;">5</span>
									Masukkan <strong>kode OTP</strong> dari SMS bank
								</li>
								<li style="padding:8px 0; display:flex;">
									<span style="color:#1979c3; font-weight:bold; min-width:25px;">6</span>
									Transaksi berhasil! Bukti pembayaran akan ditampilkan
								</li>
							</ul>
							</div>

							<div style="background:#fff8e1; border-left:4px solid #ffb300; padding:12px 15px; margin:15px 0; border-radius:0 4px 4px 0;">
								<div style="display:flex; align-items:center; margin-bottom:5px;">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="#ff6f00" style="margin-right:8px;">
										<path d="M11 15h2v2h-2zm0-8h2v6h-2zm1-5C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
									</svg>
									<strong style="color:#e65100;">Keamanan Kartu GPN:</strong>
								</div>
								<ul style="margin:5px 0 0 20px; padding-left:0; font-size:13px;">
									<li>Pastikan transaksi dilakukan di website resmi merchant</li>
									<li>Jangan bagikan kode OTP ke siapapun</li>
									<li>Logo GPN garansi keamanan transaksi</li>
								</ul>
							</div>

							<div style="margin-top:15px; text-align:center;">
								<img src="https://www.gpnindonesia.com/assets/img/logo-gpn.png" alt="Logo GPN" style="height:30px; opacity:0.8;">
								<p style="font-size:12px; color:#666; margin:5px 0 0;">Gerakan Pembayaran Nasional - Transaksi lebih cepat dan aman</p>
							</div>'
		];
	}

	public static function generalQrisGuide()
	{

		return [
			'label' => __('QRIS'),
			'content' =>  '<strong>QRIS Payment Steps</strong>
							<div style="border:1px solid #cccccc; padding:10px 20px 0; margin-bottom:15px; border-radius:4px;">
							<ul style="list-style-type: disc; padding-left:20px;">
								<li><strong>Buka aplikasi</strong> yang mendukung QRIS (E-Wallet, Mobile Banking, etc)</li>
								<li>Pilih menu <strong>QRIS / Pay / Bayar</strong></li>
								<li style="position:relative;">
									<strong>Pindai kode QR</strong> merchant
									<div style="background:#f5f5f5; padding:8px; margin:5px 0; border-radius:4px; display:inline-block;">
										<small>Pastikan kode terlihat jelas di kamera</small>
									</div>
								</li>
								<li>Verifikasi <strong>nominal pembayaran</strong></li>
								<li>Tekan <strong style="color:#1979c3;">Konfirmasi</strong></li>
								<li>Masukkan <strong>PIN/OTP</strong> aplikasi</li>
								<li>Pembayaran berhasil! Sistem akan mengalihkan Anda otomatis</li>
							</ul>
							</div>
							'
		];
	}

	public static function generalDirectDebitGuide()
	{
		return [
			'label' => __('Direct Debit'),
			'content' =>  '<strong>Jenius Pay Payment Steps</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;margin-bottom:15px;">
							<ul style="list-style-type: disc; padding-left:20px;">
								<li>Pilih Pembayaran melalui Jenius Pay</li>
								<li>Masukkan <strong>cashtag</strong> atau <strong>Nama Akun Unik</strong> Jenius Pay Anda, lalu klik konfirmasi</li>
								<li>Notifikasi dari Jenius Pay akan muncul di aplikasi</li>
								<li>Buka notifikasi tagihan di aplikasi Jenius Pay</li>
								<li>Login menggunakan kredensial Anda</li>
								<li>Cari tagihan yang ingin dibayarkan di notifikasi</li>
								<li>Verifikasi detail tagihan</li>
								<li>Klik <strong style="color:#1979c3;">Bayar</strong></li>
								<li>Transaksi selesai</li>
							</ul>
							</div>
							<div style="background:#e8f5e9; border-left:4px solid #4caf50; padding:10px 15px; margin:10px 0;">
								<strong style="color:#2e7d32;">Tips Cepat:</strong>
								<ul style="margin:5px 0 0 20px; padding-left:0;">
									<li>Pastikan aplikasi Jenius sudah diperbarui</li>
									<li>Aktifkan notifikasi push untuk Jenius Pay</li>
									<li>Cashtag biasanya berupa <code>@namapengguna</code></li>
								</ul>
							</div>
							<small>*Transaksi akan otomatis dibatalkan jika tidak dibayar dalam 24 jam</small>'
		];
	}

	public static function generalCCGuide()
	{
		return [
			'label' => __('Credit Card'),
			'content' =>  '<strong>Credit Card Payment Steps</strong>
					<div style="border:1px solid #cccccc;padding:10px 20px 0;margin-bottom:15px;">
					<ul style="list-style-type: disc; padding-left:20px;">
						<li>Pilih Pembayaran melalui Kartu Kredit</li>
						<li>Dialihkan menuju Halaman Pembayaran Kartu Kredit</li>
						<li>Masukkan informasi kartu:
							<ul style="list-style-type: circle; padding-left:25px; margin:5px 0;">
								<li>Nomor kartu</li>
								<li>Expired date (MM/YY)</li>
								<li>CVV (3 digit angka di belakang kartu)</li>
							</ul>
						</li>
						<li>Klik Konfirmasi</li>
						<li>Masukkan kode OTP yang dikirimkan ke nomor Anda</li>
						<li>Klik pilihan Konfirmasi</li>
						<li>Merchant akan memberikan bukti pembayaran</li>
					</ul>
					</div>
					<div style="background:#fff8e1; border-left:4px solid #ffc107; padding:10px 15px; margin:10px 0;">
						<strong style="color:#d32f2f;">Perhatian:</strong>
						<ul style="margin:5px 0 0 20px; padding-left:0;">
							<li>Pastikan kartu kredit Anda aktif dan memiliki limit yang cukup</li>
							<li>Biaya transaksi akan dikonversi sesuai kurs bank penerbit kartu</li>
						</ul>
					</div>'
		];
	}

	public static function bankList($bankcd = null)
	{
		$allBanks = [
			'BMRI' => [
				'label' => __('Mandiri'),
				'content' => '<strong>ATM Mandiri</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Pilih menu "Bayar"</li>
								<li>Pilih menu "Multi Payment"</li>
								<li>Masukkan "70014" sebagai Kode Perusahaan / Institusi, kemudian pilih Benar</li>
								<li>Masukkan Transferpay Kode Bayar dengan Virtual Account yang sudah didapatkan</li>
								<li>Pilih YA setelah muncul konfirmasi pembayaran</li>
								<li>Periksa kembali Nominal Pembayaran Anda pada halaman Konfirmasi Pembayaran, kemudian pilih YA</li>
								<li>Ambil bukti pembayaran Anda dan Transaksi selesai</li>
							  </ul>
							</div>
							<br><br><strong id="h4thanks">Mobile Banking</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Login Mobile Banking</li>
								<li>Pilih menu "Bayar"</li>
								<li>Pilih menu "Multi Payment"</li>
								<li>Input Transferpay sebagai penyedia jasa</li>
								<li>Input Nomor Virtual Account, misal : 70014XXXXXXXXXXX</li>
								<li>Pilih Lanjut</li>
								<li>Input OTP dan PIN</li>
								<li>Pilih OK</li>
								<li>Ambil bukti pembayaran Anda dan Transaksi selesai</li>
							  </ul>
							</div>
							<br><br><strong id="h4thanks">Internet Banking</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Login Internet Banking</li>
								<li>Pilih menu "Bayar"</li>
								<li>Pilih menu "Multi Payment"</li>
								<li>Input Transferpay sebagai penyedia jasa</li>
								<li>Input Nomor Virtual Account, misal : 70014XXXXXXXXXXX sebagai Kode Bayar</li>
								<li>Ceklis IDR</li>
								<li>Klik Lanjutkan</li>
								<li>Bukti bayar ditampilkan</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'BBBA' => [
				'label' => __('Permata Bank'),
				'content' => '<strong >ATM Permata Bank</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Masukkan Nomor PIN</li>
								<li>Pilih menu "TRANSAKSI LAINNYA"</li>
								<li>Pilih menu "PEMBAYARAN"</li>
								<li>Pilih "Pembayaran Lain-Lain"</li>
								<li>Pilih "VIRTUAL ACCOUNT";</li>
								<li>Masukkan 16 digit kode bayar (virtual account)<br/>Contoh : 8625xxxxx</li>
								<li>Pada layar akan tampil konfirmasi pembayaran</li>
								<li>Pilih "BENAR" untuk konfirmasi pembayaran</li>
								<li>Pilih "YA" agar struk / bukti transaksi keluar</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<br><br><strong id="h4thanks">Mobile Banking Permata</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Login Mobile Banking</li>
								<li>Pilih menu "Pembayaran Tagihan"</li>
								<li>Pilih menu "Virtual Account"</li>
								<li>Masukkan 16 digit kode bayar (virtual account)<br/>Misal : 8625xxxxx</li>
								<li>Masukkan Nominal Pembayaran</li>
								<li>Klik Kirim dan Input Token</li>
								<li>Pada layar akan tampil konfirmasi pembayaran</li>
								<li>Pilih "BENAR" untuk konfirmasi pembayaran</li>
								<li>Bukti transaksi akan ditampilkan</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<br><br><strong id="h4thanks">Internet Banking Permata</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Login Internet Banking</li>
								<li>Pilih menu "Pembayaran Tagihan"</li>
								<li>Pilih menu "Virtual Account"</li>
								<li>Masukkan 16 digit kode bayar (virtual account)<br/>Misal : 8625xxxxx</li>
								<li>Masukkan Nominal Pembayaran</li>
								<li>Klik Kirim dan Input Token</li>
								<li>Pada layar akan tampil konfirmasi pembayaran</li>
								<li>Klik Kirim untuk konfirmasi pembayaran</li>
								<li>Bukti transaksi akan ditampilkan</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'IBBK' => [
				'label' => __('Maybank'),
				'content' => '<strong >Maybank</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Pilih menu "Pembayaran / TOP UP Pulsa"</li>
								<li>Pilih menu "Virtual Account"</li>
								<li>Masukkan Nomor Virtual Account misal : 7812XXXXXXXXXXXX, kemudian pilih "BENAR"</li>
								<li>Periksa kembali Nominal Pembayaran Anda pada halaman Konfirmasi Pembayaran</li>
								<li>Kemudian Pilih "YA"</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'BNIN' => [
				'label' => __('BNI'),
				'content' => '<strong>ATM BNI</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Pilih menu "Menu Lain"</li>
								<li>Pilih menu "Transfer"</li>
								<li>Pilih menu "Sumber Rekening"</li>
								<li>Pilih menu "ke Rekening BNI"</li>
								<li>Pilih tipe akun Anda, misal "Rekening Tabungan"</li>
								<li>Masukkan Nomor Virtual Account, misal 8848XXXXXXXXXXXX</li>
								<li>Masukkan nominal pembayaran, kemudian pilih "BENAR"</li>
								<li>Pilih "YA" untuk konfirmasi pembayaran</li>
								<li>Ambil bukti pembayaran Anda dan transaksi selesai</li>
							  </ul>
							</div>
							<br><br><strong id="h4thanks">Mobile Banking BNI</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Login Mobile Banking</li>
								<li>Pilih menu "Transfer"</li>
								<li>Pilih menu "Within Bank"</li>
								<li>Pilih menu "Adhoc Beneficiary"</li>
								<li>Input Nomor Order, misal. Invoice-1234 sebagai Nickname</li>
								<li>Masukkan 16 digit nomor virtual account<br/>Misal : 8848xxxxx</li>
								<li>Masukkan email anda</li>
								<li>Hilangkan centang Add to Favorite list lalu klik Continue</li>
								<li>Masukkan Nominal Pembayaran pada field Amount</li>
								<li>Pilih Continue dan input password anda</li>
								<li>Bukti transaksi akan ditampilkan</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<br><br><strong id="h4thanks">Internet Banking BNI</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Login Internet Banking</li>
								<li>Pilih menu "Info dan Administrasi"</li>
								<li>Pilih menu "Atur rekening Tujuan"</li>
								<li>Input Nomor Order, misal. Invoice-1234 sebagai Nama Singkat lalu lanjutkan</li>
								<li>Masukkan 16 digit nomor virtual account <br/>Misal : 8848xxxxxxxx</li>
								<li>Klik Lanjutkan</li>
								<li>Input Token lalu Process</li>
								<li>Pilih Transfer rekening antar BNI</li>
								<li>Pilih Nomor Order, misal. Invoice-1234</li>
								<li>Masukkan Nominal Pembayaran</li>
								<li>Bukti transaksi akan ditampilkan</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'HNBN' => [
				'label' => __('KEB Hana Bank'),
				'content' => '<strong>ATM KEB Hana</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Pilih menu "Pembayaran"</li>
								<li>Pilih menu "Lainnya"</li>
								<li>Masukkan Nomor Virtual Account, misal 9772XXXXXXXXXXXX</li>
								<li>Pilih "BENAR"</li>
								<li>Pilih "YA" untuk konfirmasi pembayaran</li>
								<li>Ambil bukti pembayaran Anda dan transaksi selesai</li>
							  </ul>
							</div>
							<br><br><strong id="h4thanks">Internet Banking</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Login Internet Banking</li>
								<li>Pilih menu "Transfer"</li>
								<li>Pilih "Withdrawal Account Information"</li>
								<li>Pilih Account Number Anda</li>
								<li>Masukkan nomor virtual account <br/>Misal : 9772XXXXXXXXXXXX</li>
								<li>Masukkan Nominal Pembayaran, misal : 10000</li>
								<li>Klik Submit</li>
								<li>Input SMS Pin</li>
								<li>Bukti transaksi akan ditampilkan</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'BBBB' => [
				'label' => __('Bank Permata Syariah'),
				'content' => '<strong >ATM Permata Bank</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Masukkan Nomor PIN</li>
								<li>Pilih menu "TRANSAKSI LAINNYA"</li>
								<li>Pilih menu "PEMBAYARAN"</li>
								<li>Pilih "Pembayaran Lain-Lain"</li>
								<li>Pilih "VIRTUAL ACCOUNT";</li>
								<li>Masukkan 16 digit kode bayar (virtual account)<br/>Contoh : 8625xxxxx</li>
								<li>Pada layar akan tampil konfirmasi pembayaran</li>
								<li>Pilih "BENAR" untuk konfirmasi pembayaran</li>
								<li>Pilih "YA" agar struk / bukti transaksi keluar</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<br><br><strong id="h4thanks">Mobile Banking Permata</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Login Mobile Banking</li>
								<li>Pilih menu "Pembayaran Tagihan"</li>
								<li>Pilih menu "Virtual Account"</li>
								<li>Masukkan 16 digit kode bayar (virtual account)<br/>Misal : 8625xxxxx</li>
								<li>Masukkan Nominal Pembayaran</li>
								<li>Klik Kirim dan Input Token</li>
								<li>Pada layar akan tampil konfirmasi pembayaran</li>
								<li>Pilih "BENAR" untuk konfirmasi pembayaran</li>
								<li>Bukti transaksi akan ditampilkan</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<br><br><strong id="h4thanks">Internet Banking Permata</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							  <ul style="list-style-type: disc">
								<li>Login Internet Banking</li>
								<li>Pilih menu "Pembayaran Tagihan"</li>
								<li>Pilih menu "Virtual Account"</li>
								<li>Masukkan 16 digit kode bayar (virtual account)<br/>Misal : 8625xxxxx</li>
								<li>Masukkan Nominal Pembayaran</li>
								<li>Klik Kirim dan Input Token</li>
								<li>Pada layar akan tampil konfirmasi pembayaran</li>
								<li>Klik Kirim untuk konfirmasi pembayaran</li>
								<li>Bukti transaksi akan ditampilkan</li>
								<li>Transaksi selesai</li>
							  </ul>
							</div>
							<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'CENA' => [
				'label' => __('Bank Central Asia (BCA)'),
				'content' => '<strong>ATM BCA</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							<ul style="list-style-type: disc">
								<li>Input kartu ATM dan PIN Anda</li>
								<li>Pilih Menu Transaksi Lainnya</li>
								<li>Pilih Transfer</li>
								<li>Pilih Ke rekening BCA Virtual Account</li>
								<li>Input Nomor Virtual Account, misal. 123456789012XXXX</li>
								<li>Pilih Benar</li>
								<li>Pilih Ya</li>
								<li>Ambil bukti bayar Anda</li>
								<li>Selesai</li>
							</ul>
							</div>
							<br><br><strong id="h4thanks">Mobile Banking BCA</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							<ul style="list-style-type: disc">
								<li>Login Mobile Banking</li>
								<li>Pilih m-Transfer</li>
								<li>Pilih BCA Virtual Account</li>
								<li>Input Nomor Virtual Account, misal. 123456789012XXXX sebagai No. Virtual Account</li>
								<li>Klik Send</li>
								<li>Informasi Virtual Account akan ditampilkan</li>
								<li>Klik OK</li>
								<li>Input PIN Mobile Banking</li>
								<li>Bukti bayar ditampilkan</li>
								<li>Selesai</li>
							</ul>
							</div>
							<br><br><strong id="h4thanks">Internet Banking BCA</strong>
							<div style="border:1px solid #cccccc;padding:10px 20px 0;">
							<ul style="list-style-type: disc">
								<li>Login Internet Banking</li>
								<li>Pilih Transaksi Dana</li>
								<li>Pilih Transfer Ke BCA Virtual Account</li>
								<li>Input Nomor Virtual Account, misal. 123456789012XXXX sebagai No. Virtual Account</li>
								<li>Klik Lanjutkan</li>
								<li>Input Respon KeyBCA Appli 1</li>
								<li>Klik Kirim</li>
								<li>Bukti bayar ditampilkan</li>
								<li>Selesai</li>
							</ul>
							</div>
							<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'BRIN' => [
				'label' => __('Bank Rakyat Indonesia (BRI)'),
				'content' => '<strong>ATM BRI</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Input kartu ATM dan PIN Anda</li>
							<li>Pilih Menu Transaksi Lain</li>
							<li>Pilih Menu Pembayaran</li>
							<li>Pilih Menu Lain-lain</li>
							<li>Pilih Menu BRIVA</li>
							<li>Masukkan Nomor Virtual Account, misal. 88788XXXXXXXXXXX</li>
							<li>Pilih Ya</li>
							<li>Ambil bukti bayar anda</li>
							<li>Selesai</li>
						</ul>
						</div>
						<br><br><strong id="h4thanks">Mobile Banking BRI</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Login BRI Mobile</li>
							<li>Pilih Mobile Banking BRI</li>
							<li>Pilih Menu Pembayaran</li>
							<li>Pilih Menu BRIVA</li>
							<li>Masukkan Nomor Virtual Account, misal. 88788XXXXXXXXXXX</li>
							<li>Masukkan Nominal misal. 10000</li>
							<li>Klik Kirim</li>
							<li>Masukkan PIN Mobile</li>
							<li>Klik Kirim</li>
							<li>Bukti bayar akan dikirim melalui sms</li>
							<li>Selesai</li>
						</ul>
						</div>
						<br><br><strong id="h4thanks">Internet Banking BRI</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Login Internet Banking</li>
							<li>Pilih Pembayaran</li>
							<li>Pilih BRIVA</li>
							<li>Masukkan Nomor Virtual Account, misal. 88788XXXXXXXXXXX</li>
							<li>Klik Kirim</li>
							<li>Masukkan Password</li>
							<li>Masukkan mToken</li>
							<li>Klik Kirim</li>
							<li>Bukti bayar akan ditampilkan</li>
							<li>Selesai</li>
						</ul>
						</div>
						<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'BNIA' => [
				'label' => __('Bank PT. Bank CIMB Niaga, Tbk.'),
				'content' => '<strong>ATM CIMB Niaga</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Input kartu ATM dan PIN Anda</li>
							<li>Pilih Menu Pembayaran</li>
							<li>Pilih Menu Lanjut</li>
							<li>Pilih Menu Virtual Account</li>
							<li>Masukkan Nomor Virtual Account, misal. 5919XXXXXXXXXXXX</li>
							<li>Pilih Proses</li>
							<li>Data Virtual Account akan ditampilkan</li>
							<li>Pilih Proses</li>
							<li>Ambil bukti bayar anda</li>
							<li>Selesai</li>
						</ul>
						</div>
						<br><br><strong id="h4thanks">Mobile Banking CIMB Niaga</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Login OCTO Mobile</li>
							<li>Pilih Menu Transfer</li>
							<li>Pilih Menu Transfer ke CIMB Niaga Lain</li>
							<li>Pilih Sumber Dana yang akan digunakan</li>
							<li>Masukkan Nomor Virtual Account, misal. 5919XXXXXXXXXXXX</li>
							<li>Masukkan Nominal misal. 10000</li>
							<li>Klik Lanjut</li>
							<li>Data Virtual Account akan ditampilkan</li>
							<li>Masukkan PIN Mobile</li>
							<li>Klik Konfirmasi</li>
							<li>Bukti bayar akan dikirim melalui sms</li>
							<li>Selesai</li>
						</ul>
						</div>
						<br><br><strong id="h4thanks">Internet Banking CIMB Niaga</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Login Internet Banking</li>
							<li>Pilih Bayar Tagihan</li>
							<li>Rekening Sumber - Pilih yang akan Anda digunakan</li>
							<li>Jenis Pembayaran - Pilih Virtual Account</li>
							<li>Untuk Pembayaran - Pilih Masukkan Nomor Virtual Account</li>
							<li>Nomor Rekening Virtual, misal. 5919XXXXXXXXXXXX</li>
							<li>Isi Remark Jika diperlukan</li>
							<li>Klik Lanjut</li>
							<li>Data Virtual Account akan ditampilkan</li>
							<li>Masukkan mPIN</li>
							<li>Klik Kirim</li>
							<li>Bukti bayar akan ditampilkan</li>
							<li>Selesai</li>
						</ul>
						</div>
						<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'BDIN' => [
				'label' => __('Bank PT. Bank Danamon Indonesia, Tbk.'),
				'content' => '<strong>ATM Bank Danamon (Kartu Bank Danamon)</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Input PIN ATM Anda</li>
							<li>Pilih Menu Pembayaran >>> Virtual Account</li>
							<li>Masukan nomor Virtual Account</li>
							<li>Masukkan Nominal</li>
							<li>Pada layar konfirmasi pembayaran, pastikan transaksi sudah benar -> pilih Ya untuk memproses transaksi</li>
						</ul>
						</div>

						<br><br><strong>Aplikasi D-Mobile</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Login pada Aplikasi D-Mobile</li>
							<li>Pilih menu Virtual Account</li>
							<li>Masukan 16 digit nomor virtual account</li>
							<li>Masukan Nominal</li>
							<li>Pada layar konfirmasi pembayaran, pastikan transaksi sudah benar -> pilih Ya untuk memproses transaksi</li>
						</ul>
						</div>

						<br><br><strong>ATM Bank Danamon (Kartu Bank Lain)</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Masuk ke menu transfer</li>
							<li>Pilih tujuan rekening Bank Danamon</li>
							<li>Masukkan Kode Bank Danamon (011) + 16 digit nomor Virtual Account</li>
							<li>Masukkan nominal pembayaran</li>
							<li>Pada layar konfirmasi pembayaran, harap pastikan nama tujuan dan nominal transaksi sudah tepat</li>
							<li>Konfirmasi pembayaran</li>
						</ul>
						</div>

						<br><br><strong>ATM Bank Lain (Kartu Bank Danamon/Bank Lain)</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Masuk ke menu transfer</li>
							<li>Pilih tujuan rekening bank lain (Online Transfer)</li>
							<li>Masukkan Kode Bank Danamon (011) + 16 digit nomor Virtual Account</li>
							<li>Masukkan nominal pembayaran</li>
							<li>Pada layar konfirmasi pembayaran, harap pastikan nama tujuan dan nominal transaksi sudah tepat</li>
							<li>Konfirmasi pembayaran</li>
						</ul>
						</div>

						<br><br><strong>Internet Banking Bank Lain (ATM Bersama/ALTO/Prima)</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Masuk ke menu transfer ke bank lain</li>
							<li>Pilih transfer online</li>
							<li>Pilih Bank tujuan, Bank Danamon</li>
							<li>Masukkan 16 digit nomor Virtual Account</li>
							<li>Masukkan nominal pembayaran</li>
							<li>Pada layar konfirmasi pembayaran, harap pastikan nama tujuan dan nominal transaksi sudah tepat</li>
							<li>Konfirmasi pembayaran</li>
						</ul>
						</div>
						<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'PDJB' => [
				'label' => __('Bank BJB'),
				'content' => '<strong>ATM BJB</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Input Kartu ATM dan PIN Anda</li>
							<li>Pilih menu Transaksi Lainnya kemudian pilih Virtual Account</li>
							<li>Pilih Tabungan</li>
							<li>Input Nomor Virtual Account, misal. 1887XXXXXXXXXXXX sebagai Kode Bayar</li>
							<li>Pilih Lanjutkan</li>
							<li>Layar akan menampilkan Kode Bayar dan Data Pembayaran</li>
							<li>Jika jenis tagihan open, customer harus menginput kembali Jumlah Bayar</li>
							<li>Klik Ya untuk melakukan pembayaran</li>
							<li>Selesai</li>
						</ul>
						</div>

						<br><br><strong>Mobile Banking</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Login BJB Mobile App Anda</li>
							<li>Pilih Menu Virtual Account</li>
							<li>Input Nomor Virtual Account, misal. 1887XXXXXXXXXXXX sebagai Kode Bayar</li>
							<li>Layar akan menampilkan Kode Bayar dan Data Pembayaran</li>
							<li>Jika jenis tagihan open, customer menginput kembali Nominal yang harus dibayarkan</li>
							<li>Input PIN, Klik Lanjutkan untuk melakukan pembayaran</li>
							<li>Selesai</li>
						</ul>
						</div>

						<br><br><strong>BJB Net</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Login BJB Net App Anda</li>
							<li>Pilih Menu BJB Virtual Account</li>
							<li>Input Nomor Virtual Account, misal. 1887XXXXXXXXXXXX sebagai Kode Bayar</li>
							<li>Layar akan menampilkan Kode Bayar dan Data Pembayaran</li>
							<li>Jika jenis tagihan open, customer menginput kembali Nominal yang harus dibayarkan</li>
							<li>Klik Lanjut untuk melakukan pembayaran</li>
							<li>Selesai</li>
						</ul>
						</div>

						<br><br><strong>Transfer Bank Non-BJB</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Customer membayar tagihan melalui Channel Bank Lain (Teller, ATM atau Internet Banking)</li>
							<li>Pilih Menu Transfer antar bank</li>
							<li>Input Kode Bank BJB 110</li>
							<li>Input Nomor Virtual Account, misal. 1101887XXXXXXXXXXXX sebagai Kode Bayar</li>
							<li>System akan menampilkan Identitas dari nomor Virtual Account</li>
							<li>Jika jenis tagihan open, customer menginput kembali Nominal yang harus dibayarkan</li>
							<li>Selesai</li>
						</ul>
						</div>

						<br><br><strong>Transfer antar Bank (Dompet Digital)</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Pada Layanan Dompet Digital, pilih menu Transfer ke rekening bank</li>
							<li>Input Kode Bank dan Nomor Virtual Account</li>
							<li>Jika jenis tagihan open, customer menginput kembali Nominal yang harus dibayarkan</li>
							<li>Klik Lanjutkan untuk melakukan pembayaran</li>
							<li>Selesai</li>
						</ul>
						</div>

						<br><br><strong>Bank BJB (TELLER)</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Customer membawa nomor Virtual Account yang didapatkan dari aplikasi/website/institusi</li>
							<li>Teller Bank BJB akan menginput nomor Virtual Account tersebut pada Aplikasi BJB Fast</li>
							<li>Tergantung dengan jenis pembayaran yang ditentukan institusi/biller, customer akan membayarkan tagihannya secara fix atau open payment</li>
							<li>Customer menerima bukti bayar</li>
							<li>Status tagihan di Biller/Institusi akan otomatis berubah menjadi terbayar</li>
							<li>Selesai</li>
						</ul>
						</div>
						<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'YUDB' => [
				'label' => __('Bank Neo Commerce (BNC)'),
				'content' =>  '<strong>Internet Banking</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Masuk ke aplikasi neobank</li>
							<li>Pilih menu Pembayaran VA</li>
							<li>Pilih BNC sebagai nama bank penerima</li>
							<li>Masukkan No. VA</li>
							<li>Masukkan nominal pembayaran</li>
							<li>Konfirmasi informasi pembayaran</li>
							<li>Masukkan PIN</li>
							<li>Transaksi selesai</li>
						</ul>
						</div>
						<br><br><strong id="h4thanks">Bank Lain</strong>
						<div style="border:1px solid #cccccc;padding:10px 20px 0;">
						<ul style="list-style-type: disc">
							<li>Masuk ke aplikasi Bank App</li>
							<li>Pilih Transfer ke bank lain</li>
							<li>Pilih BNC sebagai nama bank penerima</li>
							<li>Masukkan No. VA sebagai rekening penerima</li>
							<li>Konfirmasi informasi transfer</li>
							<li>Masukkan PIN</li>
							<li>Transaksi selesai</li>
						</ul>
						</div>
						<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
			'BDKI' => [
				'label' => __('Bank DKI'),
				'content' => '<strong>ATM Bank DKI</strong>
					<div style="border:1px solid #cccccc;padding:10px 20px 0;">
					<ul style="list-style-type: disc">
						<li>Masukkan Kartu Anda</li>
						<li>Masuk ke menu utama ATM Bank DKI</li>
						<li>Pilih menu Pembayaran</li>
						<li>Pilih menu Virtual Account</li>
						<li>Masukan kode Virtual Account No Kode Verifikasi VA = 995014...</li>
						<li>Masukan Kode pembayaran (Boleh diKosongkan)</li>
						<li>Verifikasi kebenaran data jika sesuai tekan Benar, jika tidak tekan Salah</li>
						<li>Resi Transaksi pembayaran tersedia</li>
					</ul>
					</div>

					<br><br><strong>JakOne Mobile</strong>
					<div style="border:1px solid #cccccc;padding:10px 20px 0;">
					<ul style="list-style-type: disc">
						<li>Masuk ke menu utama JakOne Mobile Bank DKI</li>
						<li>Pilih menu "Pembayaran"</li>
						<li>Pilih menu "Virtual Account"</li>
						<li>Masukkan Kode Virtual Account No Kode verifikasi VA = 995014...</li>
						<li>Masukan PIN</li>
						<li>Konfirmasi tagihan pembayaran jika sesuai tekan Lanjut</li>
						<li>Resi Transaksi pembayaran tersedia</li>
					</ul>
					</div>

					<br><br><strong>EDC</strong>
					<div style="border:1px solid #cccccc;padding:10px 20px 0;">
					<ul style="list-style-type: disc">
						<li>Masuk ke menu utama EDC Bank DKI</li>
						<li>Pilih menu pembayaran VA</li>
						<li>Pilih Jenis Rekening</li>
						<li>Masukan kode Virtual Account No kode verifikasi VA = 995014...</li>
						<li>Masukan PIN</li>
						<li>Konfirmasi tagihan pembayaran jika sesuai tekan Lanjut</li>
						<li>Resi Transaksi pembayaran tersedia</li>
					</ul>
					</div>

					<br><br><strong>MPOS</strong>
					<div style="border:1px solid #cccccc;padding:10px 20px 0;">
					<ul style="list-style-type: disc">
						<li>Masuk ke menu utama MPOS bank DKI</li>
						<li>Pilih menu Pembayaran VA</li>
						<li>Masukan kode tagihan Virtual Account No kode verifikasi VA = 995014...</li>
						<li>Konfirmasi tagihan pembayaran jika sesuai tekan Lanjut</li>
						<li>Masukan Kartu</li>
						<li>Masukan PIN</li>
						<li>Resi Transaksi Pembayaran Tersedia</li>
					</ul>
					</div>

					<br><br><strong>CMS</strong>
					<div style="border:1px solid #cccccc;padding:10px 20px 0;">
					<p><strong>a. Panduan Bayar Maker (user)</strong></p>
					<ul style="list-style-type: disc">
						<li>Masuk ke menu utama CMS Bank DKI</li>
						<li>Pilih menu pembayaran tagihan</li>
						<li>Masukan kode tagihan "Virtual Account" No kode verifikasi VA = 995014...</li>
					</ul>
					<p><strong>b. Panduan Bayar Approval (user)</strong></p>
					<ul style="list-style-type: disc">
						<li>Masuk ke menu utama userApproval</li>
						<li>Pilih menu Tugas Tertunda</li>
						<li>Cek Daftar Tugas Tertunda</li>
						<li>Jika Sesuai pilih Menyetujui, jika tidak sesuai pilih Tolak</li>
						<li>Konfirmasi Tagihan</li>
					</ul>
					</div>

					<br><br><strong>Teller</strong>
					<div style="border:1px solid #cccccc;padding:10px 20px 0;">
					<p><strong>a. Panduan Bayar Tunai</strong></p>
					<ul style="list-style-type: disc">
						<li>Siapkan uang tunai</li>
						<li>Informasikan kode Tagihan Virtual Account No Kode Verifikasi VA = 995014...</li>
						<li>Bukti Transaksi Pembayaran Tersedia</li>
					</ul>
					<p><strong>b. Panduan Bayar Debet Rekening</strong></p>
					<ul style="list-style-type: disc">
						<li>Siapkan ATM/Buku Rekening tabungan dan kartu identitas</li>
						<li>Informasikan kode Tagihan Virtual Account No Kode Verifikasi VA = 995014... Kepada petugas teller</li>
						<li>Petugas teller akan menswipe kartu ATM ke mesin EDC</li>
						<li>Masukan PIN</li>
						<li>Bukti transaksi pembayaran tersedia</li>
					</ul>
					</div>
					<small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000</small>'
			],
		];

		// If specific bank requested
		if ($bankcd !== null) {
			return $allBanks[$bankcd] ?? null;
		}

		return $allBanks;
	}

	public function activeBankList()
	{
		// Get all banks
		$allBanks = $this->bankList();

		// Get active banks
		$activeBanks = $this->nicepayHelper->getActiveBanks();

		// If no active banks selected, return all
		if (empty($activeBanks)) {
			return $allBanks;
		}
		// Filter banks
		$filteredBanks = array_intersect_key($allBanks, array_flip($activeBanks));
		return !empty($filteredBanks) ? $filteredBanks : $allBanks;
	}

	public function activeMitraList($code)
	{
		$activeMitra = $this->nicepayHelper->getActiveMitra($code);

		$mitra = [];
		if ($code === 'ewallet') {
			$mitra = $this->ewalletMitraList();
		} else if ($code === 'payloan') {
			$mitra = $this->payloanMitraList();
		} else if ($code === 'cvs') {
			$mitra = $this->convenienceStoreList();
		}

		$filteredMitra = array_intersect_key($mitra, array_flip($activeMitra));

		return !empty($filteredMitra) ? $filteredMitra : $mitra;
	}



	public static function ewalletMitraList($mitraCd = null)
	{
		$mitra = [
			'DANA' => [
				'label' => __('DANA'),
				'content' => '<strong>DANA Payment Steps</strong>
            <div style="border:1px solid #cccccc;padding:10px 20px 0;margin-bottom:15px;">
            <ul style="list-style-type: disc; padding-left:20px;">
                <li>Pilih pembayaran melalui DANA dan input Nomor Seluler anda yang terdaftar</li>
                <li>Anda akan dipindahkan ke Halaman Pembayaran DANA</li>
                <li>Masuk menggunakan akun anda</li>
                <li>Pastikan Saldo anda cukup</li>
                <li>Klik Pay, Bayar atau Confirm</li>
                <li>Pembayaranmu berhasil! Anda akan dipindahkan ke result page</li>
            </ul>
            </div>
            <small>*Pastikan aplikasi DANA sudah terinstall di perangkat Anda</small>'
			],
			'OVOE' => [
				'label' => __('OVO'),
				'content' => '<strong>OVO Payment Steps</strong>
            <div style="border:1px solid #cccccc;padding:10px 20px 0;margin-bottom:15px;">
            <ul style="list-style-type: disc; padding-left:20px;">
                <li>Pilih Pembayaran melalui OVO dan Input Nomor OVO Anda</li>
                <li>Notifikasi pembayaran akan dikirimkan ke Aplikasi OVO Anda</li>
                <li>Buka dan Login pada aplikasi OVO</li>
                <li>Pastikan Saldo OVO Anda mencukupi</li>
                <li>Jika tidak mendapatkan Notifikasi, check Icon Lonceng di aplikasi</li>
                <li>Klik Pay, Bayar or Confirm</li>
                <li>Pembayaranmu berhasil! Anda akan dipindahkan ke result page</li>
            </ul>
            </div>
            <small>*Pastikan aplikasi OVO sudah terinstall dan aktif</small>'
			],
			'ESHP' => [
				'label' => __('ShopeePay'),
				'content' => '<strong>ShopeePay Payment Steps</strong>
            <div style="border:1px solid #cccccc;padding:10px 20px 0;margin-bottom:15px;">
            <ul style="list-style-type: disc; padding-left:20px;">
                <li>Pilih pembayaran melalui ShopeePay</li>
                <li>Aplikasi akan Jump App ke ShopeePay</li>
                <li>Detail pembayaran akan ditampilkan</li>
                <li>Pastikan Saldo Anda cukup</li>
                <li>Klik "Bayar Sekarang" dan masukan pin ShopeePay</li>
                <li>Pembayaranmu berhasil! Anda akan dipindahkan ke result page</li>
            </ul>
            </div>
            <small>*Hanya tersedia untuk pengguna ShopeePay</small>'
			],
			'LINK' => [
				'label' => __('LinkAja'),
				'content' => '<strong>LinkAja Payment Steps</strong>
            <div style="border:1px solid #cccccc;padding:10px 20px 0;margin-bottom:15px;">
            <ul style="list-style-type: disc; padding-left:20px;">
                <li>Pilih pembayaran melalui LinkAja</li>
                <li>Input Nomor Seluler anda yang terdaftar</li>
                <li>Anda akan dipindahkan ke Halaman Pembayaran LinkAja</li>
                <li>Masuk menggunakan akun anda</li>
                <li>Pastikan Saldo anda cukup</li>
                <li>Klik Pay, Bayar atau Confirm</li>
                <li>Pembayaranmu berhasil! Anda akan dipindahkan ke result page</li>
            </ul>
            </div>
            <small>*Pastikan akun LinkAja Anda sudah terverifikasi</small>'
			],
		];


		if ($mitraCd !== null) {
			return $mitra[$mitraCd] ?? null;
		}

		return $mitra;
	}


	public static function payoutBankList($bankCode = null)
	{
		$banks = [
			'ABAL' => [
				'label' => __('BPD BALI'),
				'content' => ''
			],
			'ABNA' => [
				'label' => __('ROYAL BANK SCOTLAND'),
				'content' => ''
			],
			'AGSS' => [
				'label' => __('BANK AGRIS'),
				'content' => ''
			],
			'AGTB' => [
				'label' => __('BRI AGRONIAGA'),
				'content' => ''
			],
			'AKTB' => [
				'label' => __('BARCLAYS INDO'),
				'content' => ''
			],
			'ANTD' => [
				'label' => __('BANK ANTARDAERAH'),
				'content' => ''
			],
			'ANZB' => [
				'label' => __('BANK ANZ INDO'),
				'content' => ''
			],
			'ARFA' => [
				'label' => __('PANIN SYARIAH'),
				'content' => ''
			],
			'ARTG' => [
				'label' => __('BANK ARTHA GRAHA'),
				'content' => ''
			],
			'ATOS' => [
				'label' => __('BANK JAGO'),
				'content' => ''
			],
			'AWAN' => [
				'label' => __('BANK QNB INDO'),
				'content' => ''
			],
			'BBAI' => [
				'label' => __('BANK BUMI ARTA'),
				'content' => ''
			],
			'BBBA' => [
				'label' => __('PT BANK PERMATA, TBK'),
				'content' => ''
			],
			'BBIJ' => [
				'label' => __('BANK UOB INDO'),
				'content' => ''
			],
			'BBUK' => [
				'label' => __('BANK BUKOPIN'),
				'content' => ''
			],
			'BCIA' => [
				'label' => __('BANK CAPITAL INDO'),
				'content' => ''
			],
			'BDIN' => [
				'label' => __('BANK DANAMON'),
				'content' => ''
			],
			'BDIP' => [
				'label' => __('SAHABAT SAMPOERNA'),
				'content' => ''
			],
			'BDKI' => [
				'label' => __('BANK DKI'),
				'content' => ''
			],
			'BDPC' => [
				'label' => __('BANK DANPAC'),
				'content' => ''
			],
			'BDSY' => [
				'label' => __('DANAMON SYARIAH'),
				'content' => ''
			],
			'BHTL' => [
				'label' => __('BANK HARMONI INTL'),
				'content' => ''
			],
			'BICN' => [
				'label' => __('COMMONWEALTH'),
				'content' => ''
			],
			'BIDX' => [
				'label' => __('BANK INDEX SELINDO'),
				'content' => ''
			],
			'BJTM' => [
				'label' => __('BANK JATIM'),
				'content' => ''
			],
			'BKCH' => [
				'label' => __('BANK OF CHINA'),
				'content' => ''
			],
			'BKKB' => [
				'label' => __('BANGKOK BANK'),
				'content' => ''
			],
			'BMDM' => [
				'label' => __('BANK MESTIKA DHARMA'),
				'content' => ''
			],
			'BMRI' => [
				'label' => __('MANDIRI'),
				'content' => ''
			],
			'BMSE' => [
				'label' => __('MULTI ARTA SENTOSA'),
				'content' => ''
			],
			'BNIA' => [
				'label' => __('CIMB NIAGA'),
				'content' => ''
			],
			'BNIN' => [
				'label' => __('BNI'),
				'content' => ''
			],
			'BNPA' => [
				'label' => __('BNP PARIBAS INDO'),
				'content' => ''
			],
			'BOFA' => [
				'label' => __('BANK OF AMERICA'),
				'content' => ''
			],
			'BOTK' => [
				'label' => __('MUFG BANK, LTD'),
				'content' => ''
			],
			'BPIA' => [
				'label' => __('RESONA PERDANIA'),
				'content' => ''
			],
			'BPKO' => [
				'label' => __('BANK PIKKO'),
				'content' => ''
			],
			'BRIN' => [
				'label' => __('BRI'),
				'content' => ''
			],
			'BSYI' => [
				'label' => __('BANK SYARIAH INDONESIA'),
				'content' => ''
			],
			'BSSP' => [
				'label' => __('BPD SUMSELBABEL'),
				'content' => ''
			],
			'BTAN' => [
				'label' => __('BTN'),
				'content' => ''
			],
			'BUMI' => [
				'label' => __('BANK MNC INTL'),
				'content' => ''
			],
			'BUST' => [
				'label' => __('KROM BANK INDONESIA'),
				'content' => ''
			],
			'BUTG' => [
				'label' => __('BANK MEGA SYARIAH'),
				'content' => ''
			],
			'BVIC' => [
				'label' => __('VICTORIA INTL'),
				'content' => ''
			],
			'BWKI' => [
				'label' => __('CHINA CONSTRUCTION'),
				'content' => ''
			],
			'CENA' => [
				'label' => __('BCA'),
				'content' => ''
			],
			'CICT' => [
				'label' => __('BANK MUTIARA'),
				'content' => ''
			],
			'CITI' => [
				'label' => __('CITIBANK'),
				'content' => ''
			],
			'CNBA' => [
				'label' => __('BANK CENTRATAMA'),
				'content' => ''
			],
			'CTCB' => [
				'label' => __('BANK CTBC INDO'),
				'content' => ''
			],
			'DBSB' => [
				'label' => __('DBS INDO'),
				'content' => ''
			],
			'DEUT' => [
				'label' => __('DEUTSCHE AG'),
				'content' => ''
			],
			'EKON' => [
				'label' => __('BANK HSBC INDO'),
				'content' => ''
			],
			'EKST' => [
				'label' => __('BANK PUNDI INDONESIA(KPO)'),
				'content' => ''
			],
			'FAMA' => [
				'label' => __('SUPERBANK'),
				'content' => ''
			],
			'GNES' => [
				'label' => __('BANK GANESHA'),
				'content' => ''
			],
			'HNBN' => [
				'label' => __('BANK KEB HANA'),
				'content' => ''
			],
			'HRDA' => [
				'label' => __('BANK HARDA INTL'),
				'content' => ''
			],
			'HVBK' => [
				'label' => __('WOORI SAUDARA INDO'),
				'content' => ''
			],
			'IBBK' => [
				'label' => __('MAYBANK INDO'),
				'content' => ''
			],
			'ICBK' => [
				'label' => __('BANK ICBC INDO'),
				'content' => ''
			],
			'INDO' => [
				'label' => __('BANK INDONESIA(KPO)'),
				'content' => ''
			],
			'INPB' => [
				'label' => __('BANK INA PERDANA'),
				'content' => ''
			],
			'JSAB' => [
				'label' => __('BANK JASA JAKARTA'),
				'content' => ''
			],
			'KSEB' => [
				'label' => __('BANK SEABANK INDONESIA'),
				'content' => ''
			],
			'LFIB' => [
				'label' => __('NOBU NATIONAL BANK'),
				'content' => ''
			],
			'LMAN' => [
				'label' => __('BANK DINAR INDO'),
				'content' => ''
			],
			'LOMA' => [
				'label' => __('BANK AMAR INDO'),
				'content' => ''
			],
			'MASD' => [
				'label' => __('BANK MASPION INDO'),
				'content' => ''
			],
			'MAYA' => [
				'label' => __('BANK MAYAPADA'),
				'content' => ''
			],
			'MAYO' => [
				'label' => __('BANK MAYORA'),
				'content' => ''
			],
			'MBBE' => [
				'label' => __('MAYBANK SYARIAH'),
				'content' => ''
			],
			'MEEK' => [
				'label' => __('BANK METRO EXPRESS'),
				'content' => ''
			],
			'MEGA' => [
				'label' => __('BANK MEGA TBK.'),
				'content' => ''
			],
			'MGAB' => [
				'label' => __('BANK MITRANIAGA'),
				'content' => ''
			],
			'MHCC' => [
				'label' => __('BANK MIZUHO INDO'),
				'content' => ''
			],
			'MUAB' => [
				'label' => __('BANK MUAMALAT'),
				'content' => ''
			],
			'NISP' => [
				'label' => __('OCBC NISP'),
				'content' => ''
			],
			'NUPA' => [
				'label' => __('BANK NUSANTARA'),
				'content' => ''
			],
			'PDAC' => [
				'label' => __('BANK ACEH'),
				'content' => ''
			],
			'PDBK' => [
				'label' => __('BPD BENGKULU'),
				'content' => ''
			],
			'PDIJ' => [
				'label' => __('BPD PAPUA'),
				'content' => ''
			],
			'PDJB' => [
				'label' => __('BANK JABAR'),
				'content' => ''
			],
			'PDJG' => [
				'label' => __('BPD JAWA TENGAH'),
				'content' => ''
			],
			'PDJM' => [
				'label' => __('BPD JAMBI'),
				'content' => ''
			],
			'PDJT' => [
				'label' => __('BANK JATIM SYARIAH'),
				'content' => ''
			],
			'PDKB' => [
				'label' => __('BPD KALBAR'),
				'content' => ''
			],
			'PDKG' => [
				'label' => __('BPD KALTENG'),
				'content' => ''
			],
			'PDKS' => [
				'label' => __('BPD KALSEL'),
				'content' => ''
			],
			'PDKT' => [
				'label' => __('BPD KALTIM'),
				'content' => ''
			],
			'PDLP' => [
				'label' => __('BPD LAMPUNG'),
				'content' => ''
			],
			'PDML' => [
				'label' => __('BPD MALUKU'),
				'content' => ''
			],
			'PDNB' => [
				'label' => __('BPD NTB'),
				'content' => ''
			],
			'PDNT' => [
				'label' => __('BPD NTT'),
				'content' => ''
			],
			'PDRI' => [
				'label' => __('BPD RIAU KEPRI'),
				'content' => ''
			],
			'PDSB' => [
				'label' => __('BPD SUMBAR'),
				'content' => ''
			],
			'PDSU' => [
				'label' => __('BPD SUMUT'),
				'content' => ''
			],
			'PDWG' => [
				'label' => __('BPD SULAWESITENGAH'),
				'content' => ''
			],
			'PDWR' => [
				'label' => __('BPD SULAWESITENGGARA'),
				'content' => ''
			],
			'PDWS' => [
				'label' => __('BANK SULSELBAR'),
				'content' => ''
			],
			'PDWU' => [
				'label' => __('BPD SULAWESIUTARA'),
				'content' => ''
			],
			'PDYK' => [
				'label' => __('BPD YOGYA SYARIAH'),
				'content' => ''
			],
			'PINB' => [
				'label' => __('PANIN'),
				'content' => ''
			],
			'PMAS' => [
				'label' => __('BANK PRIMA MASTER'),
				'content' => ''
			],
			'PUBA' => [
				'label' => __('BANK BTPN SYARIAH'),
				'content' => ''
			],
			'RABO' => [
				'label' => __('RABO BANK'),
				'content' => ''
			],
			'RIPA' => [
				'label' => __('BANK OKE INDO'),
				'content' => ''
			],
			'ROYB' => [
				'label' => __('BANK ROYAL INDO'),
				'content' => ''
			],
			'SBID' => [
				'label' => __('BANK SBI INDO'),
				'content' => ''
			],
			'SBJK' => [
				'label' => __('BANK SINARMAS'),
				'content' => ''
			],
			'SCBL' => [
				'label' => __('STANDARD CHARTERED'),
				'content' => ''
			],
			'SDOB' => [
				'label' => __('BUKOPIN SYARIAH'),
				'content' => ''
			],
			'SIHB' => [
				'label' => __('BANK SINAR HARAPAN'),
				'content' => ''
			],
			'SUNI' => [
				'label' => __('SUMITOMO MITSUI(KPO)'),
				'content' => ''
			],
			'SWAG' => [
				'label' => __('VICTORIA SYARIAH'),
				'content' => ''
			],
			'SWBA' => [
				'label' => __('BANK OF INDIA INDO'),
				'content' => ''
			],
			'SYAC' => [
				'label' => __('BPD ACEH SYARIAH'),
				'content' => ''
			],
			'SYBK' => [
				'label' => __('MAYBANK INDO UUS'),
				'content' => ''
			],
			'SYBT' => [
				'label' => __('BTN SYARIAH'),
				'content' => ''
			],
			'SYCA' => [
				'label' => __('BCA SYARIAH'),
				'content' => ''
			],
			'SYDK' => [
				'label' => __('BANK DKI SYARIAH'),
				'content' => ''
			],
			'SYJB' => [
				'label' => __('BANK JABAR SYARIAH'),
				'content' => ''
			],
			'SYKB' => [
				'label' => __('BPD KALBAR SYARIAH'),
				'content' => ''
			],
			'SYKS' => [
				'label' => __('BPD KALSEL SYARIAH'),
				'content' => ''
			],
			'SYKT' => [
				'label' => __('BPD KALTIM SYARIAH'),
				'content' => ''
			],
			'SYNA' => [
				'label' => __('CIMB NIAGA SYARIAH'),
				'content' => ''
			],
			'SYNI' => [
				'label' => __('BNI SYARIAH'),
				'content' => ''
			],
			'SYON' => [
				'label' => __('OCBC NISP SYARIAH'),
				'content' => ''
			],
			'SYSB' => [
				'label' => __('BPD SUMBAR SYARIAH'),
				'content' => ''
			],
			'SYSS' => [
				'label' => __('BPD SUMSEL SYARIAH'),
				'content' => ''
			],
			'SYSU' => [
				'label' => __('BPD SUMUT SYARIAH'),
				'content' => ''
			],
			'SYYK' => [
				'label' => __('BPD YOGYA'),
				'content' => ''
			],
			'TAPE' => [
				'label' => __('BTPN'),
				'content' => ''
			],
			'YUDB' => [
				'label' => __('BANK YUDHA BAKTI'),
				'content' => ''
			],
			'IDMC' => [
				'label' => __('INDOMARET'),
				'content' => ''
			],
			'APID' => [
				'label' => __('AIRPAY INTERNATIONAL'),
				'content' => ''
			],
			'DANA' => [
				'label' => __('ESPAY DEBIT INDONESIA KOE'),
				'content' => ''
			],
			'CHAS' => [
				'label' => __('JPMORGAN CHASE'),
				'content' => ''
			],
			'NANO' => [
				'label' => __('BANK NANO SYARIAH'),
				'content' => ''
			],
			'SYAT' => [
				'label' => __('BANK JAGO TBK UUS'),
				'content' => ''
			],
			'ALSY' => [
				'label' => __('BANK ALADIN SYARIAH TBK'),
				'content' => ''
			],
			'SHBK' => [
				'label' => __('BANK SHINHAN INDONESIA'),
				'content' => ''
			],
			'SYJM' => [
				'label' => __('BPD JAMBI UUS'),
				'content' => ''
			],
		];

		if ($bankCode === null) {
			return $banks;
		}

		return $banks[$bankCode] ?? null;
	}
}
