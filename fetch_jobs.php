<?php
/**
 * fetch_jobs.php - Return all active job listings as JSON.
 *
 * Supports optional query params for server-side filtering:
 *   ?search=keyword  (matches title/company/location/description)
 *   ?type=Full-time|Part-time|Internship
 *   ?location=city
 *
 * Each job includes an application_count for display.
 */

require_once __DIR__ . '/config/db.php';

try {
    $where  = [];
    $params = [];

    $search = trim((string)($_GET['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(j.title LIKE ? OR j.company LIKE ? OR j.location LIKE ? OR j.description LIKE ?)';
        $like     = '%' . $search . '%';
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }

    $type = (string)($_GET['type'] ?? '');
    if (in_array($type, ['Full-time', 'Part-time', 'Internship'], true)) {
        $where[]  = 'j.type = ?';
        $params[] = $type;
    }

    $location = trim((string)($_GET['location'] ?? ''));
    if ($location !== '') {
        $where[]  = 'j.location LIKE ?';
        $params[] = '%' . $location . '%';
    }

    $sql = '
        SELECT
            j.id, j.title, j.company, j.location, j.type,
            j.description, j.salary, j.created_at,
            u.name AS employer_name,
            (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS application_count
        FROM jobs j
        INNER JOIN users u ON u.id = j.employer_id
    ';

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY j.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();

    json_response(['success' => true, 'jobs' => $jobs]);
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Could not load jobs.'], 500);
}
