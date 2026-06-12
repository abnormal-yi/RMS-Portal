<?php
function formatDate(string $date, string $format = 'M d, Y'): string {
    $ts = strtotime($date);
    if (!$ts) return $date;
    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $month = $months[(int)date('n', $ts) - 1];
    $day = date('d', $ts);
    $year = date('Y', $ts);
    return str_replace(['M','d','Y'], [$month, $day, $year], $format);
}

function formatCurrency($amount): string {
    return 'TZS ' . number_format((float)$amount, 0, '.', ',');
}

function statusBadge(string $status): string {
    $classes = [
        'available' => 'bg-green-100 text-green-800',
        'rented' => 'bg-blue-100 text-blue-800',
        'maintenance' => 'bg-yellow-100 text-yellow-800',
        'active' => 'bg-green-100 text-green-800',
        'terminated' => 'bg-red-100 text-red-800',
        'completed' => 'bg-green-100 text-green-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'resolved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
    ];
    $class = $classes[$status] ?? 'bg-gray-100 text-gray-800';
    return "<span class=\"inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize {$class}\">{$status}</span>";
}

function hsc($str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
