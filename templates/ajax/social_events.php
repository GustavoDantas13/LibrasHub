<?php
declare(strict_types=1);
session_start();
if(empty($_SESSION['usuario_id'])){http_response_code(401);exit;}
require_once __DIR__.'/../configs/config.php';require_once __DIR__.'/../configs/social_schema.php';ensureSocialSchema($pdo);
$uid=(int)$_SESSION['usuario_id'];$chat=(int)($_GET['chat']??0);$last=(int)($_SERVER['HTTP_LAST_EVENT_ID']??$_GET['after']??0);
$check=$pdo->prepare('SELECT 1 FROM social_chat_membros WHERE id_chat=? AND id_usuario=?');$check->execute([$chat,$uid]);if(!$check->fetchColumn()){http_response_code(403);exit;}
session_write_close();header('Content-Type: text/event-stream');header('Cache-Control: no-cache');header('X-Accel-Buffering: no');
$start=time();while(time()-$start<25){$s=$pdo->prepare('SELECT m.*,u.nm_usuario,u.foto_perfil,rm.texto resposta_texto,rm.tipo_anexo resposta_tipo_anexo,ru.nm_usuario resposta_autor FROM social_mensagens m JOIN usuario u ON u.id_usuario=m.id_usuario LEFT JOIN social_mensagens rm ON rm.id_mensagem=m.id_mensagem_resposta LEFT JOIN usuario ru ON ru.id_usuario=rm.id_usuario WHERE m.id_chat=? AND m.id_mensagem>? ORDER BY m.id_mensagem LIMIT 50');$s->execute([$chat,$last]);foreach($s->fetchAll() as $m){$last=(int)$m['id_mensagem'];echo "id: {$last}\nevent: message\ndata: ".json_encode($m,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n\n";}if(connection_aborted())break;echo ": keepalive\n\n";@ob_flush();flush();usleep(900000);}echo "event: reconnect\ndata: {}\n\n";
