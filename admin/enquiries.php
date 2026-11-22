<?php
require __DIR__ . '/_init.php';
require_can('enquiries');
$adminTitle = 'Enquiries';
$activeNav = 'enquiries';

const ENQUIRY_STATUSES = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied'];

$status = (string) ($_GET['status'] ?? '');
$status = isset(ENQUIRY_STATUSES[$status]) ? $status : '';
$search = trim((string) ($_GET['q'] ?? ''));

$where = [];
$params = [];
if ($status) {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR message LIKE ?)';
    array_push($params, "%{$search}%", "%{$search}%", "%{$search}%");
}
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// CSV export of the current filter
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="enquiries-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8
    fputcsv($out, ['Received', 'Name', 'Email', 'Country', 'Arrival', 'Departure', 'Travellers', 'Holiday type', 'Message', 'Status']);
    foreach (q('SELECT * FROM enquiries' . $sqlWhere . ' ORDER BY created_at DESC', $params) as $r) {
        $row = [$r['created_at'], $r['name'], $r['email'], $r['country'], $r['arrival'], $r['departure'], $r['travellers'], $r['holiday_type'], $r['message'], $r['status']];
        // Stop spreadsheet formula injection
        fputcsv($out, array_map(fn ($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, $row));
    }
    exit;
}

$perPage = 25;
$total = (int) q('SELECT COUNT(*) FROM enquiries' . $sqlWhere, $params)->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$pageNo = min($pages, max(1, (int) ($_GET['p'] ?? 1)));
$rows = q('SELECT * FROM enquiries' . $sqlWhere . ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . (($pageNo - 1) * $perPage), $params)->fetchAll();
$qs = fn (array $extra) => http_build_query(array_filter(array_merge(['status' => $status, 'q' => $search], $extra), fn ($v) => $v !== '' && $v !== null));

require __DIR__ . '/_header.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Enquiries</h1>
    <a href="enquiries.php?<?= e($qs(['export' => 1])) ?>" class="btn btn-sm btn-success shadow-sm"><i class="fas fa-file-csv fa-sm"></i> Export to Excel (CSV)</a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <form class="form-inline" method="get">
            <div class="btn-group mr-3 mb-2 mb-md-0">
                <a href="enquiries.php?<?= e($qs(['status' => ''])) ?>" class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-light' ?>">All</a>
                <?php foreach (ENQUIRY_STATUSES as $k => $label): ?>
                    <a href="enquiries.php?<?= e($qs(['status' => $k])) ?>" class="btn btn-sm <?= $status === $k ? 'btn-primary' : 'btn-light' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
            <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <input class="form-control form-control-sm mr-2" name="q" value="<?= e($search) ?>" placeholder="Search name, email, message">
            <button class="btn btn-sm btn-outline-primary">Search</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Received</th><th>Name</th><th>Country</th><th>Dates</th><th>Pax</th><th>Type</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr class="<?= $r['status'] === 'new' ? 'font-weight-bold' : '' ?>">
                    <td><small><?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></small></td>
                    <td><a href="enquiry.php?id=<?= (int) $r['id'] ?>"><?= e($r['name']) ?></a><br><small class="text-muted"><?= e($r['email']) ?></small></td>
                    <td><?= e($r['country']) ?></td>
                    <td><small><?= $r['arrival'] ? e(date('d M Y', strtotime($r['arrival']))) : '–' ?><?= $r['departure'] ? ' → ' . e(date('d M Y', strtotime($r['departure']))) : '' ?></small></td>
                    <td><?= e($r['travellers']) ?></td>
                    <td><?= e($r['holiday_type'] ?: '–') ?></td>
                    <td><span class="badge badge-<?= ['new' => 'warning', 'read' => 'info', 'replied' => 'success'][$r['status']] ?? 'secondary' ?>"><?= e(ENQUIRY_STATUSES[$r['status']] ?? $r['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No enquiries found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div class="card-footer">
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item<?= $i === $pageNo ? ' active' : '' ?>"><a class="page-link" href="enquiries.php?<?= e($qs(['p' => $i])) ?>"><?= $i ?></a></li>
                <?php endfor; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
