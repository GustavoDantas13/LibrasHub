<?php
declare(strict_types=1);
require_once __DIR__ . '/../configs/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(bool $ok, array $data = [], int $status = 200): never {
    http_response_code($status);
    echo json_encode(['ok' => $ok] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if (empty($_SESSION['usuario_id'])) respond(false, ['message' => 'Sua sessão expirou. Entre novamente.'], 401);
$userId = (int) $_SESSION['usuario_id'];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS comunidade_publicacao (
      id_publicacao INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      id_usuario INT NOT NULL, tipo ENUM('discussao','evento','grupo') NOT NULL DEFAULT 'discussao',
      titulo VARCHAR(140) NOT NULL, conteudo TEXT NOT NULL, tags VARCHAR(255) NULL,
      criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_comunidade_tipo_data (tipo, criado_em), INDEX idx_comunidade_usuario (id_usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS comunidade_comentario (
      id_comentario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, id_publicacao INT UNSIGNED NOT NULL,
      id_usuario INT NOT NULL, conteudo VARCHAR(1000) NOT NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_comentario_publicacao (id_publicacao, criado_em),
      CONSTRAINT fk_comentario_publicacao FOREIGN KEY (id_publicacao) REFERENCES comunidade_publicacao(id_publicacao) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS comunidade_curtida (
      id_publicacao INT UNSIGNED NOT NULL, id_usuario INT NOT NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY(id_publicacao,id_usuario),
      CONSTRAINT fk_curtida_publicacao FOREIGN KEY (id_publicacao) REFERENCES comunidade_publicacao(id_publicacao) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) { respond(false, ['message' => 'Não foi possível preparar a Comunidade.'], 500); }

$method = $_SERVER['REQUEST_METHOD'];
$input = $method === 'POST' ? json_decode(file_get_contents('php://input'), true) : $_GET;
if (!is_array($input)) $input = [];
$action = (string)($input['action'] ?? 'list');
if ($method === 'POST' && !hash_equals((string)($_SESSION['community_csrf'] ?? ''), (string)($input['csrf'] ?? ''))) respond(false, ['message' => 'Atualize a página e tente novamente.'], 403);

try {
  if ($action === 'list') {
    $type = in_array(($input['type'] ?? ''), ['discussao','evento','grupo'], true) ? $input['type'] : '';
    $search = trim((string)($input['search'] ?? ''));
    $where=[];$params=[];
    if($type){$where[]='p.tipo = ?';$params[]=$type;}
    if($search!==''){$where[]='(p.titulo LIKE ? OR p.conteudo LIKE ? OR p.tags LIKE ?)';$q='%'.mb_substr($search,0,80).'%';array_push($params,$q,$q,$q);}
    $sql="SELECT p.*, u.nm_usuario autor,
      (SELECT COUNT(*) FROM comunidade_curtida l WHERE l.id_publicacao=p.id_publicacao) curtidas,
      (SELECT COUNT(*) FROM comunidade_comentario c WHERE c.id_publicacao=p.id_publicacao) comentarios,
      EXISTS(SELECT 1 FROM comunidade_curtida l2 WHERE l2.id_publicacao=p.id_publicacao AND l2.id_usuario=?) curtiu
      FROM comunidade_publicacao p JOIN usuario u ON u.id_usuario=p.id_usuario";
    array_unshift($params,$userId); if($where)$sql.=' WHERE '.implode(' AND ',$where);$sql.=' ORDER BY p.criado_em DESC LIMIT 100';
    $stmt=$pdo->prepare($sql);$stmt->execute($params);respond(true,['posts'=>$stmt->fetchAll()]);
  }
  if ($action === 'comments') {
    $id=(int)($input['postId']??0);$stmt=$pdo->prepare("SELECT c.*,u.nm_usuario autor FROM comunidade_comentario c JOIN usuario u ON u.id_usuario=c.id_usuario WHERE c.id_publicacao=? ORDER BY c.criado_em");$stmt->execute([$id]);respond(true,['comments'=>$stmt->fetchAll()]);
  }
  if ($action === 'create') {
    $title=trim((string)($input['title']??''));$content=trim((string)($input['content']??''));$type=(string)($input['type']??'discussao');$tags=trim((string)($input['tags']??''));
    if(mb_strlen($title)<4||mb_strlen($title)>140||mb_strlen($content)<10||mb_strlen($content)>5000||!in_array($type,['discussao','evento','grupo'],true))respond(false,['message'=>'Revise o título e o conteúdo da publicação.'],422);
    $stmt=$pdo->prepare('INSERT INTO comunidade_publicacao(id_usuario,tipo,titulo,conteudo,tags) VALUES(?,?,?,?,?)');$stmt->execute([$userId,$type,$title,$content,mb_substr($tags,0,255)]);respond(true,['message'=>'Publicação criada com sucesso.','id'=>(int)$pdo->lastInsertId()]);
  }
  if ($action === 'comment') {
    $id=(int)($input['postId']??0);$content=trim((string)($input['content']??''));if($id<1||mb_strlen($content)<1||mb_strlen($content)>1000)respond(false,['message'=>'O comentário deve ter entre 1 e 1.000 caracteres.'],422);
    $stmt=$pdo->prepare('INSERT INTO comunidade_comentario(id_publicacao,id_usuario,conteudo) SELECT id_publicacao,?,? FROM comunidade_publicacao WHERE id_publicacao=?');$stmt->execute([$userId,$content,$id]);if(!$stmt->rowCount())respond(false,['message'=>'Publicação não encontrada.'],404);respond(true,['message'=>'Comentário publicado.']);
  }
  if ($action === 'like') {
    $id=(int)($input['postId']??0);$stmt=$pdo->prepare('SELECT 1 FROM comunidade_curtida WHERE id_publicacao=? AND id_usuario=?');$stmt->execute([$id,$userId]);if($stmt->fetchColumn()){$pdo->prepare('DELETE FROM comunidade_curtida WHERE id_publicacao=? AND id_usuario=?')->execute([$id,$userId]);$liked=false;}else{$pdo->prepare('INSERT INTO comunidade_curtida(id_publicacao,id_usuario) SELECT id_publicacao,? FROM comunidade_publicacao WHERE id_publicacao=?')->execute([$userId,$id]);$liked=true;}respond(true,['liked'=>$liked]);
  }
  if ($action === 'delete') {
    $id=(int)($input['postId']??0);$stmt=$pdo->prepare('DELETE FROM comunidade_publicacao WHERE id_publicacao=? AND id_usuario=?');$stmt->execute([$id,$userId]);respond($stmt->rowCount()>0,['message'=>$stmt->rowCount()?'Publicação excluída.':'Você não pode excluir esta publicação.'],$stmt->rowCount()?200:403);
  }
  respond(false,['message'=>'Ação inválida.'],400);
} catch(PDOException $e){respond(false,['message'=>'Não foi possível concluir a ação agora.'],500);}
