<?php
/**
 * PQR API Endpoint - Parking The Beasts
 * Fixed: column names match schema (user_id, id), sendSuccess → sendResponse
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';

setApiHeaders();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();

    switch ($action) {
        case 'create':
            if ($method !== 'POST') sendError('Método no permitido', 405);

            $userId = requireAuth();
            $data   = getJsonInput();

            if (empty($data['type']) || empty($data['description'])) {
                sendError('Tipo y descripción son requeridos', 400);
            }

            $validTypes = ['PETITION', 'COMPLAINT', 'CLAIM', 'SUGGESTION'];
            if (!in_array(strtoupper($data['type']), $validTypes)) {
                sendError('Tipo inválido. Debe ser: PETITION, COMPLAINT, CLAIM o SUGGESTION', 400);
            }

            $stmt = $db->prepare("
                INSERT INTO pqr (user_id, type, description, status, created_at)
                VALUES (?, ?, ?, 'PENDING', NOW())
            ");
            $stmt->execute([
                $userId,
                strtoupper($data['type']),
                trim($data['description'])
            ]);

            $pqrId = $db->lastInsertId();

            sendResponse([
                'success' => true,
                'message' => 'Solicitud creada exitosamente',
                'pqr_id'  => $pqrId
            ], 201);
            break;

        case 'list':
            if ($method !== 'GET') sendError('Método no permitido', 405);

            $userId = requireAuth();

            $stmt = $db->prepare("
                SELECT id, type, description, status, response, created_at, updated_at
                FROM pqr
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $pqrs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            sendResponse(['success' => true, 'pqrs' => $pqrs]);
            break;

        case 'detail':
            if ($method !== 'GET') sendError('Método no permitido', 405);

            $userId = requireAuth();
            $id     = $_GET['id'] ?? null;

            if (!$id) sendError('ID de PQR requerido', 400);

            $stmt = $db->prepare("
                SELECT id, type, description, status, response, created_at, updated_at
                FROM pqr
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $userId]);
            $pqr = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pqr) sendError('PQR no encontrada', 404);

            sendResponse(['success' => true, 'pqr' => $pqr]);
            break;

        case 'update':
            if ($method !== 'PUT') sendError('Método no permitido', 405);

            $userId = requireAuth();

            // Only admin can update PQR status
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (($_SESSION['role'] ?? '') !== 'ADMIN') {
                sendError('Solo los administradores pueden actualizar el estado de un PQR', 403);
            }

            $id   = $_GET['id'] ?? null;
            $data = getJsonInput();

            if (!$id) sendError('ID de PQR requerido', 400);

            $validStatuses = ['PENDING', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'];
            if (!empty($data['status']) && !in_array(strtoupper($data['status']), $validStatuses)) {
                sendError('Estado inválido', 400);
            }

            $updateFields = [];
            $params       = [];

            if (!empty($data['status'])) {
                $updateFields[] = 'status = ?';
                $params[]       = strtoupper($data['status']);
            }

            if (!empty($data['response'])) {
                $updateFields[] = 'response = ?';
                $params[]       = $data['response'];
            }

            if (empty($updateFields)) sendError('No hay campos para actualizar', 400);

            $updateFields[] = 'updated_at = NOW()';
            $params[]       = $id;

            $sql  = "UPDATE pqr SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            sendResponse(['success' => true, 'message' => 'PQR actualizada exitosamente']);
            break;

        case 'all':
            if ($method !== 'GET') sendError('Método no permitido', 405);

            requireAdmin();

            $limit  = max(1, (int)($_GET['limit']  ?? 50));
            $offset = max(0, (int)($_GET['offset'] ?? 0));

            $stmt = $db->prepare("
                SELECT p.id, p.type, p.description, p.status, p.response,
                       p.created_at, p.updated_at, u.full_name, u.email
                FROM pqr p
                JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $pqrs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = $db->query("SELECT COUNT(*) FROM pqr")->fetchColumn();

            sendResponse([
                'success' => true,
                'pqrs'    => $pqrs,
                'total'   => $total,
                'limit'   => $limit,
                'offset'  => $offset
            ]);
            break;

        default:
            sendError('Acción no válida', 400);
    }

} catch (PDOException $e) {
    error_log("PQR API Error: " . $e->getMessage());
    sendError('Error de base de datos', 500);
} catch (Exception $e) {
    error_log("PQR API Error: " . $e->getMessage());
    sendError($e->getMessage(), 500);
}
