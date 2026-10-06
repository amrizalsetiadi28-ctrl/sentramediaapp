// Auto-fokus ke input pertama pada form (memudahkan admin saat input data)
document.addEventListener('DOMContentLoaded', function () {
  var firstInput = document.querySelector('form.auto-focus input:not([type=hidden])');
  if (firstInput) {
    firstInput.focus();
  }
});

// Format angka menjadi format ribuan Indonesia untuk tampilan (dipakai di form transaksi)
function formatAngka(angka) {
  angka = Math.round(Number(angka) || 0);
  return angka.toLocaleString('id-ID');
}
