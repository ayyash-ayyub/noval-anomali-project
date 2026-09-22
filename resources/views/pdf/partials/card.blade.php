@php
    /** @var \App\Models\Voucher $voucher */
@endphp
<div class="voucher-card">
    <p class="brand">NOVAL ANOMALI &middot; WIFI VOUCHER</p>
    <p class="title">WIFI ACCESS</p>
    <table class="fields">
        <tr>
            <td class="label">Username</td>
            <td class="value">{{ $voucher->username }}</td>
        </tr>
        <tr>
            <td class="label">Password</td>
            <td class="value">{{ $voucher->password }}</td>
        </tr>
        <tr>
            <td class="label">Package</td>
            <td class="value">{{ $voucher->profile }}</td>
        </tr>
    </table>
    <p class="footer">WiFi Login Information &mdash; {{ $voucher->mikrotik->name }}</p>
</div>
