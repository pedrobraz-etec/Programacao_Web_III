<?php
session_start();
$host="localhost"; $usuari<?php
session_start();
$host="localhost"; $usuario="root"; $senha=""; $banco="bd_mundo";
$conexao=new mysqli($host,$usuario,$senha,$banco);
if($conexao->connect_error) die("Erro de conexão: ".$conexao->connect_error);
$conexao->set_charset("utf8mb4");
function e($v){return htmlspecialchars($v??"",ENT_QUOTES,"UTF-8");}
function dataBR($v){return $v?date("d/m/Y",strtotime($v)):"-";}
function num($v){return number_format((float)$v,2,",",".");}
define("MAX_TENTATIVAS",3);

function registrarLog($conexao,$usuarioId,$loginTentado,$sucesso,$mensagem){
 $ip=$_SERVER["REMOTE_ADDR"]??"desconhecido";
 $s=$conexao->prepare("INSERT INTO logs(usuario_id,login_tentado,sucesso,mensagem,ip) VALUES(?,?,?,?,?)");
 $s->bind_param("isiss",$usuarioId,$loginTentado,$sucesso,$mensagem,$ip);
 $s->execute();
}

$mensagem=""; $erro=""; $erroLogin=""; $mensagemLogin="";

if(isset($_GET["logout"])){
 session_unset();
 session_destroy();
 header("Location: index.php");
 exit;
}

if($_SERVER["REQUEST_METHOD"]==="POST" && ($_POST["acao"]??"")==="login"){
 $loginDigitado=trim($_POST["login"]??"");
 $senhaDigitada=$_POST["senha"]??"";
 $s=$conexao->prepare("SELECT * FROM usuarios WHERE login=?");
 $s->bind_param("s",$loginDigitado);
 $s->execute();
 $u=$s->get_result()->fetch_assoc();

 if(!$u){
  registrarLog($conexao,null,$loginDigitado,0,"Usuário não encontrado");
  $erroLogin="Login ou senha inválidos.";
 }elseif($u["bloqueado"]==1){
  registrarLog($conexao,$u["id"],$loginDigitado,0,"Tentativa em usuário bloqueado");
  $erroLogin="Usuário bloqueado após 3 tentativas incorretas. Procure o administrador.";
 }elseif(password_verify($senhaDigitada,$u["senha"])){
   $conexao->query("UPDATE usuarios SET tentativas_falhas=0 WHERE id=".(int)$u["id"]);
  registrarLog($conexao,$u["id"],$loginDigitado,1,"Login efetuado com sucesso");
  session_regenerate_id(true);
  $_SESSION["usuario_id"]=$u["id"];
  $_SESSION["usuario_nome"]=$u["nome"];
  $_SESSION["usuario_login"]=$u["login"];
  $_SESSION["primeiro_acesso"]=(int)$u["primeiro_acesso"];
  header("Location: index.php");
  exit;
 }else{
  $novasTentativas=$u["tentativas_falhas"]+1;
  if($novasTentativas>=MAX_TENTATIVAS){
   $conexao->query("UPDATE usuarios SET tentativas_falhas=$novasTentativas, bloqueado=1 WHERE id=".(int)$u["id"]);
   registrarLog($conexao,$u["id"],$loginDigitado,0,"Senha incorreta - usuário bloqueado após 3 tentativas");
   $erroLogin="Senha incorreta. Limite de tentativas atingido: usuário bloqueado.";
  }else{
   $conexao->query("UPDATE usuarios SET tentativas_falhas=$novasTentativas WHERE id=".(int)$u["id"]);
   registrarLog($conexao,$u["id"],$loginDigitado,0,"Senha incorreta");
   $restantes=MAX_TENTATIVAS-$novasTentativas;
   $erroLogin="Senha incorreta. Você tem mais $restantes tentativa(s) antes do bloqueio.";
  }
 }
}

if($_SERVER["REQUEST_METHOD"]==="POST" && ($_POST["acao"]??"")==="trocar_senha" && isset($_SESSION["usuario_id"])){
 $novaSenha=$_POST["nova_senha"]??"";
 $confirmarSenha=$_POST["confirmar_senha"]??"";
 if(strlen($novaSenha)<6){
  $erroLogin="A nova senha deve ter pelo menos 6 caracteres.";
 }elseif($novaSenha!==$confirmarSenha){
  $erroLogin="As senhas digitadas não coincidem.";
 }else{
  $hash=password_hash($novaSenha,PASSWORD_DEFAULT);
  $s=$conexao->prepare("UPDATE usuarios SET senha=?, primeiro_acesso=0 WHERE id=?");
  $s->bind_param("si",$hash,$_SESSION["usuario_id"]);
  if($s->execute()){
   registrarLog($conexao,$_SESSION["usuario_id"],$_SESSION["usuario_login"],1,"Senha alterada no primeiro acesso");
   $_SESSION["primeiro_acesso"]=0;
   $mensagemLogin="Senha alterada com sucesso!";
  }else{
   $erroLogin="Não foi possível alterar a senha.";
  }
 }
}

$logado=isset($_SESSION["usuario_id"]);
$precisaTrocarSenha=$logado && (int)($_SESSION["primeiro_acesso"]??0)===1;

$pagina=$_GET["pagina"]??"home"; $mensagem=""; $erro="";

$erroSenha=""; $mensagemSenha="";
if($_SERVER["REQUEST_METHOD"]==="POST" && ($_POST["acao"]??"")==="alterar_senha" && $logado && !$precisaTrocarSenha){
 $senhaAtual=$_POST["senha_atual"]??"";
 $novaSenha=$_POST["nova_senha"]??"";
 $confirmarSenha=$_POST["confirmar_senha"]??"";

 $s=$conexao->prepare("SELECT * FROM usuarios WHERE id=?");
 $s->bind_param("i",$_SESSION["usuario_id"]);
 $s->execute();
 $u=$s->get_result()->fetch_assoc();

 if(!$u || !password_verify($senhaAtual,$u["senha"])){
  registrarLog($conexao,$_SESSION["usuario_id"],$_SESSION["usuario_login"],0,"Tentativa de troca de senha com senha atual incorreta");
  $erroSenha="Senha atual incorreta.";
 }elseif(strlen($novaSenha)<6){
  $erroSenha="A nova senha deve ter pelo menos 6 caracteres.";
 }elseif($novaSenha!==$confirmarSenha){
  $erroSenha="A nova senha e a confirmação não coincidem.";
 }elseif(password_verify($novaSenha,$u["senha"])){
  $erroSenha="A nova senha deve ser diferente da senha atual.";
 }else{
  $hash=password_hash($novaSenha,PASSWORD_DEFAULT);
  $s=$conexao->prepare("UPDATE usuarios SET senha=? WHERE id=?");
  $s->bind_param("si",$hash,$_SESSION["usuario_id"]);
  if($s->execute()){
   registrarLog($conexao,$_SESSION["usuario_id"],$_SESSION["usuario_login"],1,"Senha alterada pelo próprio usuário");
   $mensagemSenha="Senha alterada com sucesso!";
  }else{
   $erroSenha="Não foi possível alterar a senha.";
  }
 }
 $pagina="alterar_senha";
}
if($_SERVER["REQUEST_METHOD"]==="POST" && $logado && !$precisaTrocarSenha){
 try{
  $a=$_POST["acao"]??"";
  if($a==="cadastrar_continente"||$a==="editar_continente"){
   if($a==="cadastrar_continente"){
    $s=$conexao->prepare("INSERT INTO continentes(nome,populacao,area,total_paises) VALUES(?,?,?,?)");
    $s->bind_param("sidi",$_POST["nome"],$_POST["populacao"],$_POST["area"],$_POST["total_paises"]);
   }else{
    $s=$conexao->prepare("UPDATE continentes SET nome=?,populacao=?,area=?,total_paises=? WHERE id=?");
    $s->bind_param("sidii",$_POST["nome"],$_POST["populacao"],$_POST["area"],$_POST["total_paises"],$_POST["id"]);
   }
   $s->execute(); $mensagem="Continente salvo com sucesso!";
  }
  if($a==="cadastrar_governante"||$a==="editar_governante"){
   if($a==="cadastrar_governante"){
    $s=$conexao->prepare("INSERT INTO governantes(nome,partido_politico,data_nascimento,idade,inicio_mandato,fim_mandato) VALUES(?,?,?,?,?,?)");
    $s->bind_param("sssiss",$_POST["nome"],$_POST["partido_politico"],$_POST["data_nascimento"],$_POST["idade"],$_POST["inicio_mandato"],$_POST["fim_mandato"]);
   }else{
    $s=$conexao->prepare("UPDATE governantes SET nome=?,partido_politico=?,data_nascimento=?,idade=?,inicio_mandato=?,fim_mandato=? WHERE id=?");
    $s->bind_param("sssissi",$_POST["nome"],$_POST["partido_politico"],$_POST["data_nascimento"],$_POST["idade"],$_POST["inicio_mandato"],$_POST["fim_mandato"],$_POST["id"]);
   }
   $s->execute(); $mensagem="Governante salvo com sucesso!";
  }
  if($a==="cadastrar_pais"||$a==="editar_pais"){
   if($a==="cadastrar_pais"){
    $s=$conexao->prepare("INSERT INTO paises(nome,continente_id,populacao,area,idioma,governante_id,clima,regime_politico,moeda) VALUES(?,?,?,?,?,?,?,?,?)");
    $s->bind_param("siidsisss",$_POST["nome"],$_POST["continente_id"],$_POST["populacao"],$_POST["area"],$_POST["idioma"],$_POST["governante_id"],$_POST["clima"],$_POST["regime_politico"],$_POST["moeda"]);
   }else{
    $s=$conexao->prepare("UPDATE paises SET nome=?,continente_id=?,populacao=?,area=?,idioma=?,governante_id=?,clima=?,regime_politico=?,moeda=? WHERE id=?");
    $s->bind_param("siidsisssi",$_POST["nome"],$_POST["continente_id"],$_POST["populacao"],$_POST["area"],$_POST["idioma"],$_POST["governante_id"],$_POST["clima"],$_POST["regime_politico"],$_POST["moeda"],$_POST["id"]);
   }
   $s->execute(); $mensagem="País salvo com sucesso!";
  }
  if($a==="cadastrar_cidade"||$a==="editar_cidade"){
   if($a==="cadastrar_cidade"){
    $s=$conexao->prepare("INSERT INTO cidades(nome,pais_id,populacao,area,clima,governante_id,data_fundacao) VALUES(?,?,?,?,?,?,?)");
    $s->bind_param("siidsss",$_POST["nome"],$_POST["pais_id"],$_POST["populacao"],$_POST["area"],$_POST["clima"],$_POST["governante_id"],$_POST["data_fundacao"]);
   }else{
    $s=$conexao->prepare("UPDATE cidades SET nome=?,pais_id=?,populacao=?,area=?,clima=?,governante_id=?,data_fundacao=? WHERE id=?");
    $s->bind_param("siidsssi",$_POST["nome"],$_POST["pais_id"],$_POST["populacao"],$_POST["area"],$_POST["clima"],$_POST["governante_id"],$_POST["data_fundacao"],$_POST["id"]);
   }
   $s->execute(); $mensagem="Cidade salva com sucesso!";
  }
 }catch(Throwable $x){$erro="Não foi possível realizar a operação. Verifique os dados e os relacionamentos.";}
}
if(isset($_GET["excluir"],$_GET["tipo"]) && $logado && !$precisaTrocarSenha){
 $map=["continente"=>"continentes","governante"=>"governantes","pais"=>"paises","cidade"=>"cidades"];
 $id=(int)$_GET["excluir"]; $tipo=$_GET["tipo"];
 if(isset($map[$tipo])) try{$s=$conexao->prepare("DELETE FROM ".$map[$tipo]." WHERE id=?");$s->bind_param("i",$id);$s->execute();$mensagem="Registro excluído com sucesso!";}catch(Throwable $x){$erro="Não foi possível excluir. O registro possui vínculos no banco.";}
}
?>
<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CRUD Mundo</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,sans-serif;background:#eef3f8;color:#17202a}header{background:linear-gradient(135deg,#063970,#087fbd);color:#fff;padding:28px 5%}header h1{font-size:30px}header p{margin-top:6px}nav{background:#fff;padding:14px 5%;display:flex;gap:10px;flex-wrap:wrap;box-shadow:0 2px 8px #0001}nav a{color:#063970;text-decoration:none;font-weight:bold;padding:10px 15px;border-radius:8px}nav a:hover,nav a.ativo{background:#087fbd;color:#fff}.container{width:90%;max-width:1250px;margin:28px auto}.card,.stat{background:#fff;border-radius:14px;padding:24px;box-shadow:0 5px 20px #00000012;margin-bottom:22px}h2{color:#063970;margin-bottom:18px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}label{display:block;font-weight:bold;margin-bottom:6px}input,select{width:100%;padding:11px;border:1px solid #ccd6df;border-radius:8px}button,.btn{display:inline-block;border:0;border-radius:8px;padding:10px 16px;font-weight:bold;cursor:pointer;text-decoration:none}.btn-success{background:#198754;color:#fff}.btn-warning{background:#f0ad00;color:#111}.btn-danger{background:#dc3545;color:#fff}.btn-secondary{background:#6c757d;color:#fff}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{padding:11px;border-bottom:1px solid #e1e7ec;text-align:left}th{background:#063970;color:#fff}.actions{display:flex;gap:7px;flex-wrap:wrap}.alert{padding:13px 16px;border-radius:8px;margin-bottom:18px}.success{background:#d1e7dd;color:#0f5132}.error{background:#f8d7da;color:#842029}.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px}.stat strong{display:block;font-size:28px;color:#087fbd;margin-top:8px}.search{margin-bottom:15px}footer{text-align:center;color:#667;padding:25px}@media(max-width:700px){table{display:block;overflow-x:auto;white-space:nowrap}}
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}.login-box{background:#fff;border-radius:14px;padding:36px;box-shadow:0 5px 25px #00000018;width:100%;max-width:380px}.login-box h2{color:#063970;margin-bottom:6px;text-align:center}.login-box p.subtitulo{text-align:center;color:#667;margin-bottom:22px;font-size:14px}.login-box label{display:block;font-weight:bold;margin-bottom:6px;margin-top:14px}.login-box input{width:100%;padding:11px;border:1px solid #ccd6df;border-radius:8px}.login-box button{width:100%;margin-top:22px;padding:12px;background:#087fbd;color:#fff;border:0;border-radius:8px;font-weight:bold;cursor:pointer}.login-box button:hover{background:#063970}.user-bar{display:flex;justify-content:flex-end;align-items:center;gap:14px;padding:10px 5%;background:#fff;font-size:14px;color:#063970}.user-bar a{color:#dc3545;font-weight:bold;text-decoration:none}
</style></head><body>
<?php if(!$logado): ?>
<div class="login-wrap"><div class="login-box">
<h2>CRUD Mundo</h2><p class="subtitulo">Acesso ao sistema</p>
<?php if($erroLogin):?><div class="alert error"><?=e($erroLogin)?></div><?php endif;?>
<?php if($mensagemLogin):?><div class="alert success"><?=e($mensagemLogin)?></div><?php endif;?>
<form method="post">
<input type="hidden" name="acao" value="login">
<label>Login</label><input type="text" name="login" required autofocus>
<label>Senha</label><input type="password" name="senha" required>
<button>Entrar</button>
</form>
</div></div>

<?php elseif($precisaTrocarSenha): ?>
<div class="login-wrap"><div class="login-box">
<h2>Troca de senha obrigatória</h2><p class="subtitulo">Este é o seu primeiro acesso. Defina uma nova senha para continuar.</p>
<?php if($erroLogin):?><div class="alert error"><?=e($erroLogin)?></div><?php endif;?>
<form method="post">
<input type="hidden" name="acao" value="trocar_senha">
<label>Nova senha</label><input type="password" name="nova_senha" minlength="6" required>
<label>Confirmar nova senha</label><input type="password" name="confirmar_senha" minlength="6" required>
<button>Salvar nova senha</button>
</form>
</div></div>

<?php else: ?>
<header><h1>CRUD Mundo</h1><p>Gerenciamento de Continentes, Países, Cidades e Governantes</p></header>
<div class="user-bar">Olá, <strong>&nbsp;<?=e($_SESSION["usuario_nome"])?></strong>&nbsp;|&nbsp;<a href="?pagina=alterar_senha">Alterar senha</a>&nbsp;|&nbsp;<a href="?logout=1">Sair</a></div>
<nav><a class="<?= $pagina==='home'?'ativo':''?>" href="index.php">Início</a><a class="<?= $pagina==='continentes'?'ativo':''?>" href="?pagina=continentes">Continentes</a><a class="<?= $pagina==='governantes'?'ativo':''?>" href="?pagina=governantes">Governantes</a><a class="<?= $pagina==='paises'?'ativo':''?>" href="?pagina=paises">Países</a><a class="<?= $pagina==='cidades'?'ativo':''?>" href="?pagina=cidades">Cidades</a><a class="<?= $pagina==='alterar_senha'?'ativo':''?>" href="?pagina=alterar_senha">Alterar senha</a></nav>
<div class="container"><?php if($mensagem):?><div class="alert success"><?=e($mensagem)?></div><?php endif;?><?php if($erro):?><div class="alert error"><?=e($erro)?></div><?php endif;?>

<?php if($pagina==="home"):?>
<div class="card"><h2>Bem-vindo ao CRUD Mundo</h2><p>Use o menu para cadastrar, consultar, editar e excluir informações.</p></div>
<div class="stats"><?php foreach(["Continentes"=>"continentes","Países"=>"paises","Cidades"=>"cidades","Governantes"=>"governantes"] as $n=>$t):$q=$conexao->query("SELECT COUNT(*) total FROM $t")->fetch_assoc()["total"];?><div class="stat"><?=e($n)?><strong><?=$q?></strong></div><?php endforeach;?></div>

<?php elseif($pagina==="continentes"):
$ed=(int)($_GET["editar"]??0);$r=$ed?$conexao->query("SELECT * FROM continentes WHERE id=$ed")->fetch_assoc():null;?>
<div class="card"><h2><?=$r?"Editar":"Cadastrar"?> Continente</h2><form method="post" class="grid"><input type="hidden" name="acao" value="<?=$r?'editar_continente':'cadastrar_continente'?>"><?php if($r):?><input type="hidden" name="id" value="<?=$r['id']?>"><?php endif;?><div><label>Nome</label><input name="nome" value="<?=e($r['nome']??'')?>" required></div><div><label>População</label><input type="number" name="populacao" value="<?=e($r['populacao']??'')?>" required></div><div><label>Área (km²)</label><input type="number" step=".01" name="area" value="<?=e($r['area']??'')?>" required></div><div><label>Total de países</label><input type="number" name="total_paises" value="<?=e($r['total_paises']??'')?>" required></div><div><button class="btn btn-success"><?= $r?"Atualizar":"Cadastrar"?></button></div></form></div>
<div class="card"><h2>Continentes cadastrados</h2><input class="search" placeholder="Pesquisar..." oninput="filtrar(this)"><table><tr><th>ID</th><th>Nome</th><th>População</th><th>Área</th><th>Países</th><th>Ações</th></tr><?php $q=$conexao->query("SELECT * FROM continentes ORDER BY nome");while($x=$q->fetch_assoc()):?><tr><td><?=$x['id']?></td><td><?=e($x['nome'])?></td><td><?=number_format($x['populacao'],0,",",".")?></td><td><?=num($x['area'])?></td><td><?=$x['total_paises']?></td><td class="actions"><a class="btn btn-warning" href="?pagina=continentes&editar=<?=$x['id']?>">Editar</a><a class="btn btn-danger" href="?pagina=continentes&excluir=<?=$x['id']?>&tipo=continente" onclick="return confirm('Excluir?')">Excluir</a></td></tr><?php endwhile;?></table></div>

<?php elseif($pagina==="governantes"):
$ed=(int)($_GET["editar"]??0);$r=$ed?$conexao->query("SELECT * FROM governantes WHERE id=$ed")->fetch_assoc():null;?>
<div class="card"><h2><?=$r?"Editar":"Cadastrar"?> Governante</h2><form method="post" class="grid"><input type="hidden" name="acao" value="<?=$r?'editar_governante':'cadastrar_governante'?>"><?php if($r):?><input type="hidden" name="id" value="<?=$r['id']?>"><?php endif;?><div><label>Nome</label><input name="nome" value="<?=e($r['nome']??'')?>" required></div><div><label>Partido político</label><input name="partido_politico" value="<?=e($r['partido_politico']??'')?>" required></div><div><label>Data de nascimento</label><input type="date" name="data_nascimento" value="<?=e($r['data_nascimento']??'')?>" required></div><div><label>Idade</label><input type="number" name="idade" value="<?=e($r['idade']??'')?>" required></div><div><label>Início do mandato</label><input type="date" name="inicio_mandato" value="<?=e($r['inicio_mandato']??'')?>" required></div><div><label>Fim do mandato</label><input type="date" name="fim_mandato" value="<?=e($r['fim_mandato']??'')?>" required></div><div><button class="btn btn-success"><?=$r?"Atualizar":"Cadastrar"?></button></div></form></div>
<div class="card"><h2>Governantes cadastrados</h2><input class="search" placeholder="Pesquisar..." oninput="filtrar(this)"><table><tr><th>ID</th><th>Nome</th><th>Partido</th><th>Nascimento</th><th>Idade</th><th>Início</th><th>Fim</th><th>Ações</th></tr><?php $q=$conexao->query("SELECT * FROM governantes ORDER BY nome");while($x=$q->fetch_assoc()):?><tr><td><?=$x['id']?></td><td><?=e($x['nome'])?></td><td><?=e($x['partido_politico'])?></td><td><?=dataBR($x['data_nascimento'])?></td><td><?=$x['idade']?></td><td><?=dataBR($x['inicio_mandato'])?></td><td><?=dataBR($x['fim_mandato'])?></td><td class="actions"><a class="btn btn-warning" href="?pagina=governantes&editar=<?=$x['id']?>">Editar</a><a class="btn btn-danger" href="?pagina=governantes&excluir=<?=$x['id']?>&tipo=governante" onclick="return confirm('Excluir?')">Excluir</a></td></tr><?php endwhile;?></table></div>

<?php elseif($pagina==="paises"):
$ed=(int)($_GET["editar"]??0);$r=$ed?$conexao->query("SELECT * FROM paises WHERE id=$ed")->fetch_assoc():null;$cs=$conexao->query("SELECT * FROM continentes ORDER BY nome");$gs=$conexao->query("SELECT * FROM governantes ORDER BY nome");?>
<div class="card"><h2><?=$r?"Editar":"Cadastrar"?> País</h2><form method="post" class="grid"><input type="hidden" name="acao" value="<?=$r?'editar_pais':'cadastrar_pais'?>"><?php if($r):?><input type="hidden" name="id" value="<?=$r['id']?>"><?php endif;?><div><label>Nome</label><input name="nome" value="<?=e($r['nome']??'')?>" required></div><div><label>Continente</label><select name="continente_id" required><option value="">Selecione</option><?php while($c=$cs->fetch_assoc()):?><option value="<?=$c['id']?>" <?=($r['continente_id']??0)==$c['id']?"selected":""?>><?=e($c['nome'])?></option><?php endwhile;?></select></div><div><label>População</label><input type="number" name="populacao" value="<?=e($r['populacao']??'')?>" required></div><div><label>Área</label><input type="number" step=".01" name="area" value="<?=e($r['area']??'')?>" required></div><div><label>Idioma</label><input name="idioma" value="<?=e($r['idioma']??'')?>" required></div><div><label>Governante</label><select name="governante_id" required><option value="">Selecione</option><?php while($g=$gs->fetch_assoc()):?><option value="<?=$g['id']?>" <?=($r['governante_id']??0)==$g['id']?"selected":""?>><?=e($g['nome'])?></option><?php endwhile;?></select></div><div><label>Clima</label><input name="clima" value="<?=e($r['clima']??'')?>" required></div><div><label>Regime político</label><input name="regime_politico" value="<?=e($r['regime_politico']??'')?>" required></div><div><label>Moeda</label><input name="moeda" value="<?=e($r['moeda']??'')?>" required></div><div><button class="btn btn-success"><?=$r?"Atualizar":"Cadastrar"?></button></div></form></div>
<div class="card"><h2>Países cadastrados</h2><input class="search" placeholder="Pesquisar..." oninput="filtrar(this)"><table><tr><th>ID</th><th>Nome</th><th>Continente</th><th>População</th><th>Idioma</th><th>Clima</th><th>Moeda</th><th>Ações</th></tr><?php $q=$conexao->query("SELECT p.*,c.nome continente FROM paises p LEFT JOIN continentes c ON c.id=p.continente_id ORDER BY p.nome");while($x=$q->fetch_assoc()):?><tr><td><?=$x['id']?></td><td><?=e($x['nome'])?></td><td><?=e($x['continente'])?></td><td><?=number_format($x['populacao'],0,",",".")?></td><td><?=e($x['idioma'])?></td><td><?=e($x['clima'])?></td><td><?=e($x['moeda'])?></td><td class="actions"><a class="btn btn-warning" href="?pagina=paises&editar=<?=$x['id']?>">Editar</a><a class="btn btn-danger" href="?pagina=paises&excluir=<?=$x['id']?>&tipo=pais" onclick="return confirm('Excluir país? As cidades vinculadas serão excluídas.')">Excluir</a></td></tr><?php endwhile;?></table></div>

<?php elseif($pagina==="alterar_senha"):?>
<div class="card" style="max-width:420px;margin:0 auto">
<h2>Alterar senha de acesso</h2>
<?php if($erroSenha):?><div class="alert error"><?=e($erroSenha)?></div><?php endif;?>
<?php if($mensagemSenha):?><div class="alert success"><?=e($mensagemSenha)?></div><?php endif;?>
<form method="post">
<input type="hidden" name="acao" value="alterar_senha">
<label>Senha atual</label><input type="password" name="senha_atual" required>
<label>Nova senha</label><input type="password" name="nova_senha" minlength="6" required>
<label>Confirmar nova senha</label><input type="password" name="confirmar_senha" minlength="6" required>
<button class="btn btn-success" style="margin-top:18px;width:100%">Salvar nova senha</button>
</form>
</div>

<?php elseif($pagina==="cidades"):
$ed=(int)($_GET["editar"]??0);$r=$ed?$conexao->query("SELECT * FROM cidades WHERE id=$ed")->fetch_assoc():null;$ps=$conexao->query("SELECT * FROM paises ORDER BY nome");$gs=$conexao->query("SELECT * FROM governantes ORDER BY nome");?>
<div class="card"><h2><?=$r?"Editar":"Cadastrar"?> Cidade</h2><form method="post" class="grid"><input type="hidden" name="acao" value="<?=$r?'editar_cidade':'cadastrar_cidade'?>"><?php if($r):?><input type="hidden" name="id" value="<?=$r['id']?>"><?php endif;?><div><label>Nome</label><input name="nome" value="<?=e($r['nome']??'')?>" required></div><div><label>País</label><select name="pais_id" required><option value="">Selecione</option><?php while($p=$ps->fetch_assoc()):?><option value="<?=$p['id']?>" <?=($r['pais_id']??0)==$p['id']?"selected":""?>><?=e($p['nome'])?></option><?php endwhile;?></select></div><div><label>População</label><input type="number" name="populacao" value="<?=e($r['populacao']??'')?>" required></div><div><label>Área</label><input type="number" step=".01" name="area" value="<?=e($r['area']??'')?>" required></div><div><label>Clima</label><input name="clima" value="<?=e($r['clima']??'')?>" required></div><div><label>Governante</label><select name="governante_id" required><option value="">Selecione</option><?php while($g=$gs->fetch_assoc()):?><option value="<?=$g['id']?>" <?=($r['governante_id']??0)==$g['id']?"selected":""?>><?=e($g['nome'])?></option><?php endwhile;?></select></div><div><label>Data de fundação</label><input type="date" name="data_fundacao" value="<?=e($r['data_fundacao']??'')?>" required></div><div><button class="btn btn-success"><?=$r?"Atualizar":"Cadastrar"?></button></div></form></div>
<div class="card"><h2>Cidades cadastradas</h2><input class="search" placeholder="Pesquisar..." oninput="filtrar(this)"><table><tr><th>ID</th><th>Nome</th><th>País</th><th>População</th><th>Área</th><th>Clima</th><th>Fundação</th><th>Ações</th></tr><?php $q=$conexao->query("SELECT c.*,p.nome pais FROM cidades c LEFT JOIN paises p ON p.id=c.pais_id ORDER BY c.nome");while($x=$q->fetch_assoc()):?><tr><td><?=$x['id']?></td><td><?=e($x['nome'])?></td><td><?=e($x['pais'])?></td><td><?=number_format($x['populacao'],0,",",".")?></td><td><?=num($x['area'])?></td><td><?=e($x['clima'])?></td><td><?=dataBR($x['data_fundacao'])?></td><td class="actions"><a class="btn btn-warning" href="?pagina=cidades&editar=<?=$x['id']?>">Editar</a><a class="btn btn-danger" href="?pagina=cidades&excluir=<?=$x['id']?>&tipo=cidade" onclick="return confirm('Excluir cidade?')">Excluir</a></td></tr><?php endwhile;?></table></div>
<?php endif;?></div><footer>CRUD Mundo - Programação Web</footer>
<script>function filtrar(i){let t=i.value.toLowerCase();i.parentElement.querySelectorAll("tbody tr").forEach(r=>r.style.display=r.innerText.toLowerCase().includes(t)?"":"none")}</script>
<?php endif; ?>
</body></html>
o="root"; $senha=""; $banco="bd_mundo";
$conexao=new mysqli($host,$usuario,$senha,$banco);
if($conexao->connect_error) die("Erro de conexão: ".$conexao->connect_error);
$conexao->set_charset("utf8mb4");
function e($v){return htmlspecialchars($v??"",ENT_QUOTES,"UTF-8");}
function dataBR($v){return $v?date("d/m/Y",strtotime($v)):"-";}
function num($v){return number_format((float)$v,2,",",".");}
define("MAX_TENTATIVAS",3);

function registrarLog($conexao,$usuarioId,$loginTentado,$sucesso,$mensagem){
 $ip=$_SERVER["REMOTE_ADDR"]??"desconhecido";
 $s=$conexao->prepare("INSERT INTO logs(usuario_id,login_tentado,sucesso,mensagem,ip) VALUES(?,?,?,?,?)");
 $s->bind_param("isiss",$usuarioId,$loginTentado,$sucesso,$mensagem,$ip);
 $s->execute();
}

$mensagem=""; $erro=""; $erroLogin=""; $mensagemLogin="";

if(isset($_GET["logout"])){
 session_unset();
 session_destroy();
 header("Location: index.php");
 exit;
}

if($_SERVER["REQUEST_METHOD"]==="POST" && ($_POST["acao"]??"")==="login"){
 $loginDigitado=trim($_POST["login"]??"");
 $senhaDigitada=$_POST["senha"]??"";
 $s=$conexao->prepare("SELECT * FROM usuarios WHERE login=?");
 $s->bind_param("s",$loginDigitado);
 $s->execute();
 $u=$s->get_result()->fetch_assoc();

 if(!$u){
  registrarLog($conexao,null,$loginDigitado,0,"Usuário não encontrado");
  $erroLogin="Login ou senha inválidos.";
 }elseif($u["bloqueado"]==1){
  registrarLog($conexao,$u["id"],$loginDigitado,0,"Tentativa em usuário bloqueado");
  $erroLogin="Usuário bloqueado após 3 tentativas incorretas. Procure o administrador.";
 }elseif(password_verify($senhaDigitada,$u["senha"])){
  // login correto: zera tentativas e cria sessão
  $conexao->query("UPDATE usuarios SET tentativas_falhas=0 WHERE id=".(int)$u["id"]);
  registrarLog($conexao,$u["id"],$loginDigitado,1,"Login efetuado com sucesso");
  $_SESSION["usuario_id"]=$u["id"];
  $_SESSION["usuario_nome"]=$u["nome"];
  $_SESSION["usuario_login"]=$u["login"];
  $_SESSION["primeiro_acesso"]=(int)$u["primeiro_acesso"];
  header("Location: index.php");
  exit;
 }else{
  // senha incorreta: incrementa tentativas
  $novasTentativas=$u["tentativas_falhas"]+1;
  if($novasTentativas>=MAX_TENTATIVAS){
   $conexao->query("UPDATE usuarios SET tentativas_falhas=$novasTentativas, bloqueado=1 WHERE id=".(int)$u["id"]);
   registrarLog($conexao,$u["id"],$loginDigitado,0,"Senha incorreta - usuário bloqueado após 3 tentativas");
   $erroLogin="Senha incorreta. Limite de tentativas atingido: usuário bloqueado.";
  }else{
   $conexao->query("UPDATE usuarios SET tentativas_falhas=$novasTentativas WHERE id=".(int)$u["id"]);
   registrarLog($conexao,$u["id"],$loginDigitado,0,"Senha incorreta");
   $restantes=MAX_TENTATIVAS-$novasTentativas;
   $erroLogin="Senha incorreta. Você tem mais $restantes tentativa(s) antes do bloqueio.";
  }
 }
}

if($_SERVER["REQUEST_METHOD"]==="POST" && ($_POST["acao"]??"")==="trocar_senha" && isset($_SESSION["usuario_id"])){
 $novaSenha=$_POST["nova_senha"]??"";
 $confirmarSenha=$_POST["confirmar_senha"]??"";
 if(strlen($novaSenha)<6){
  $erroLogin="A nova senha deve ter pelo menos 6 caracteres.";
 }elseif($novaSenha!==$confirmarSenha){
  $erroLogin="As senhas digitadas não coincidem.";
 }else{
  $hash=password_hash($novaSenha,PASSWORD_DEFAULT);
  $s=$conexao->prepare("UPDATE usuarios SET senha=?, primeiro_acesso=0 WHERE id=?");
  $s->bind_param("si",$hash,$_SESSION["usuario_id"]);
  $s->execute();
  $_SESSION["primeiro_acesso"]=0;
  $mensagemLogin="Senha alterada com sucesso!";
 }
}

$logado=isset($_SESSION["usuario_id"]);
$precisaTrocarSenha=$logado && (int)($_SESSION["primeiro_acesso"]??0)===1;

$pagina=$_GET["pagina"]??"home"; $mensagem=""; $erro="";

$erroSenha=""; $mensagemSenha="";
if($_SERVER["REQUEST_METHOD"]==="POST" && ($_POST["acao"]??"")==="alterar_senha" && $logado && !$precisaTrocarSenha){
 $senhaAtual=$_POST["senha_atual"]??"";
 $novaSenha=$_POST["nova_senha"]??"";
 $confirmarSenha=$_POST["confirmar_senha"]??"";

 $s=$conexao->prepare("SELECT * FROM usuarios WHERE id=?");
 $s->bind_param("i",$_SESSION["usuario_id"]);
 $s->execute();
 $u=$s->get_result()->fetch_assoc();

 if(!$u || !password_verify($senhaAtual,$u["senha"])){
  registrarLog($conexao,$_SESSION["usuario_id"],$_SESSION["usuario_login"],0,"Tentativa de troca de senha com senha atual incorreta");
  $erroSenha="Senha atual incorreta.";
 }elseif(strlen($novaSenha)<6){
  $erroSenha="A nova senha deve ter pelo menos 6 caracteres.";
 }elseif($novaSenha!==$confirmarSenha){
  $erroSenha="A nova senha e a confirmação não coincidem.";
 }elseif(password_verify($novaSenha,$u["senha"])){
  $erroSenha="A nova senha deve ser diferente da senha atual.";
 }else{
  $hash=password_hash($novaSenha,PASSWORD_DEFAULT);
  $s=$conexao->prepare("UPDATE usuarios SET senha=? WHERE id=?");
  $s->bind_param("si",$hash,$_SESSION["usuario_id"]);
  $s->execute();
  registrarLog($conexao,$_SESSION["usuario_id"],$_SESSION["usuario_login"],1,"Senha alterada pelo próprio usuário");
  $mensagemSenha="Senha alterada com sucesso!";
 }
 $pagina="alterar_senha";
}
if($_SERVER["REQUEST_METHOD"]==="POST" && $logado && !$precisaTrocarSenha){
 try{
  $a=$_POST["acao"]??"";
  if($a==="cadastrar_continente"||$a==="editar_continente"){
   if($a==="cadastrar_continente"){
    $s=$conexao->prepare("INSERT INTO continentes(nome,populacao,area,total_paises) VALUES(?,?,?,?)");
    $s->bind_param("sidi",$_POST["nome"],$_POST["populacao"],$_POST["area"],$_POST["total_paises"]);
   }else{
    $s=$conexao->prepare("UPDATE continentes SET nome=?,populacao=?,area=?,total_paises=? WHERE id=?");
    $s->bind_param("sidii",$_POST["nome"],$_POST["populacao"],$_POST["area"],$_POST["total_paises"],$_POST["id"]);
   }
   $s->execute(); $mensagem="Continente salvo com sucesso!";
  }
  if($a==="cadastrar_governante"||$a==="editar_governante"){
   if($a==="cadastrar_governante"){
    $s=$conexao->prepare("INSERT INTO governantes(nome,partido_politico,data_nascimento,idade,inicio_mandato,fim_mandato) VALUES(?,?,?,?,?,?)");
    $s->bind_param("sssiss",$_POST["nome"],$_POST["partido_politico"],$_POST["data_nascimento"],$_POST["idade"],$_POST["inicio_mandato"],$_POST["fim_mandato"]);
   }else{
    $s=$conexao->prepare("UPDATE governantes SET nome=?,partido_politico=?,data_nascimento=?,idade=?,inicio_mandato=?,fim_mandato=? WHERE id=?");
    $s->bind_param("sssissi",$_POST["nome"],$_POST["partido_politico"],$_POST["data_nascimento"],$_POST["idade"],$_POST["inicio_mandato"],$_POST["fim_mandato"],$_POST["id"]);
   }
   $s->execute(); $mensagem="Governante salvo com sucesso!";
  }
  if($a==="cadastrar_pais"||$a==="editar_pais"){
   if($a==="cadastrar_pais"){
    $s=$conexao->prepare("INSERT INTO paises(nome,continente_id,populacao,area,idioma,governante_id,clima,regime_politico,moeda) VALUES(?,?,?,?,?,?,?,?,?)");
    $s->bind_param("siidsisss",$_POST["nome"],$_POST["continente_id"],$_POST["populacao"],$_POST["area"],$_POST["idioma"],$_POST["governante_id"],$_POST["clima"],$_POST["regime_politico"],$_POST["moeda"]);
   }else{
    $s=$conexao->prepare("UPDATE paises SET nome=?,continente_id=?,populacao=?,area=?,idioma=?,governante_id=?,clima=?,regime_politico=?,moeda=? WHERE id=?");
    $s->bind_param("siidsisssi",$_POST["nome"],$_POST["continente_id"],$_POST["populacao"],$_POST["area"],$_POST["idioma"],$_POST["governante_id"],$_POST["clima"],$_POST["regime_politico"],$_POST["moeda"],$_POST["id"]);
   }
   $s->execute(); $mensagem="País salvo com sucesso!";
  }
  if($a==="cadastrar_cidade"||$a==="editar_cidade"){
   if($a==="cadastrar_cidade"){
    $s=$conexao->prepare("INSERT INTO cidades(nome,pais_id,populacao,area,clima,governante_id,data_fundacao) VALUES(?,?,?,?,?,?,?)");
    $s->bind_param("siidsss",$_POST["nome"],$_POST["pais_id"],$_POST["populacao"],$_POST["area"],$_POST["clima"],$_POST["governante_id"],$_POST["data_fundacao"]);
   }else{
    $s=$conexao->prepare("UPDATE cidades SET nome=?,pais_id=?,populacao=?,area=?,clima=?,governante_id=?,data_fundacao=? WHERE id=?");
    $s->bind_param("siidsssi",$_POST["nome"],$_POST["pais_id"],$_POST["populacao"],$_POST["area"],$_POST["clima"],$_POST["governante_id"],$_POST["data_fundacao"],$_POST["id"]);
   }
   $s->execute(); $mensagem="Cidade salva com sucesso!";
  }
 }catch(Throwable $x){$erro="Não foi possível realizar a operação. Verifique os dados e os relacionamentos.";}
}
if(isset($_GET["excluir"],$_GET["tipo"]) && $logado && !$precisaTrocarSenha){
 $map=["continente"=>"continentes","governante"=>"governantes","pais"=>"paises","cidade"=>"cidades"];
 $id=(int)$_GET["excluir"]; $tipo=$_GET["tipo"];
 if(isset($map[$tipo])) try{$s=$conexao->prepare("DELETE FROM ".$map[$tipo]." WHERE id=?");$s->bind_param("i",$id);$s->execute();$mensagem="Registro excluído com sucesso!";}catch(Throwable $x){$erro="Não foi possível excluir. O registro possui vínculos no banco.";}
}
?>
<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CRUD Mundo</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,sans-serif;background:#eef3f8;color:#17202a}header{background:linear-gradient(135deg,#063970,#087fbd);color:#fff;padding:28px 5%}header h1{font-size:30px}header p{margin-top:6px}nav{background:#fff;padding:14px 5%;display:flex;gap:10px;flex-wrap:wrap;box-shadow:0 2px 8px #0001}nav a{color:#063970;text-decoration:none;font-weight:bold;padding:10px 15px;border-radius:8px}nav a:hover,nav a.ativo{background:#087fbd;color:#fff}.container{width:90%;max-width:1250px;margin:28px auto}.card,.stat{background:#fff;border-radius:14px;padding:24px;box-shadow:0 5px 20px #00000012;margin-bottom:22px}h2{color:#063970;margin-bottom:18px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}label{display:block;font-weight:bold;margin-bottom:6px}input,select{width:100%;padding:11px;border:1px solid #ccd6df;border-radius:8px}button,.btn{display:inline-block;border:0;border-radius:8px;padding:10px 16px;font-weight:bold;cursor:pointer;text-decoration:none}.btn-success{background:#198754;color:#fff}.btn-warning{background:#f0ad00;color:#111}.btn-danger{background:#dc3545;color:#fff}.btn-secondary{background:#6c757d;color:#fff}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{padding:11px;border-bottom:1px solid #e1e7ec;text-align:left}th{background:#063970;color:#fff}.actions{display:flex;gap:7px;flex-wrap:wrap}.alert{padding:13px 16px;border-radius:8px;margin-bottom:18px}.success{background:#d1e7dd;color:#0f5132}.error{background:#f8d7da;color:#842029}.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px}.stat strong{display:block;font-size:28px;color:#087fbd;margin-top:8px}.search{margin-bottom:15px}footer{text-align:center;color:#667;padding:25px}@media(max-width:700px){table{display:block;overflow-x:auto;white-space:nowrap}}
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}.login-box{background:#fff;border-radius:14px;padding:36px;box-shadow:0 5px 25px #00000018;width:100%;max-width:380px}.login-box h2{color:#063970;margin-bottom:6px;text-align:center}.login-box p.subtitulo{text-align:center;color:#667;margin-bottom:22px;font-size:14px}.login-box label{display:block;font-weight:bold;margin-bottom:6px;margin-top:14px}.login-box input{width:100%;padding:11px;border:1px solid #ccd6df;border-radius:8px}.login-box button{width:100%;margin-top:22px;padding:12px;background:#087fbd;color:#fff;border:0;border-radius:8px;font-weight:bold;cursor:pointer}.login-box button:hover{background:#063970}.user-bar{display:flex;justify-content:flex-end;align-items:center;gap:14px;padding:10px 5%;background:#fff;font-size:14px;color:#063970}.user-bar a{color:#dc3545;font-weight:bold;text-decoration:none}
</style></head><body>
<?php if(!$logado): ?>
<div class="login-wrap"><div class="login-box">
<h2>CRUD Mundo</h2><p class="subtitulo">Acesso ao sistema</p>
<?php if($erroLogin):?><div class="alert error"><?=e($erroLogin)?></div><?php endif;?>
<?php if($mensagemLogin):?><div class="alert success"><?=e($mensagemLogin)?></div><?php endif;?>
<form method="post">
<input type="hidden" name="acao" value="login">
<label>Login</label><input type="text" name="login" required autofocus>
<label>Senha</label><input type="password" name="senha" required>
<button>Entrar</button>
</form>
</div></div>

<?php elseif($precisaTrocarSenha): ?>
<div class="login-wrap"><div class="login-box">
<h2>Troca de senha obrigatória</h2><p class="subtitulo">Este é o seu primeiro acesso. Defina uma nova senha para continuar.</p>
<?php if($erroLogin):?><div class="alert error"><?=e($erroLogin)?></div><?php endif;?>
<form method="post">
<input type="hidden" name="acao" value="trocar_senha">
<label>Nova senha</label><input type="password" name="nova_senha" minlength="6" required>
<label>Confirmar nova senha</label><input type="password" name="confirmar_senha" minlength="6" required>
<button>Salvar nova senha</button>
</form>
</div></div>

<?php else: ?>
<header><h1>CRUD Mundo</h1><p>Gerenciamento de Continentes, Países, Cidades e Governantes</p></header>
<div class="user-bar">Olá, <strong>&nbsp;<?=e($_SESSION["usuario_nome"])?></strong>&nbsp;|&nbsp;<a href="?pagina=alterar_senha">Alterar senha</a>&nbsp;|&nbsp;<a href="?logout=1">Sair</a></div>
<nav><a class="<?= $pagina==='home'?'ativo':''?>" href="index.php">Início</a><a class="<?= $pagina==='continentes'?'ativo':''?>" href="?pagina=continentes">Continentes</a><a class="<?= $pagina==='governantes'?'ativo':''?>" href="?pagina=governantes">Governantes</a><a class="<?= $pagina==='paises'?'ativo':''?>" href="?pagina=paises">Países</a><a class="<?= $pagina==='cidades'?'ativo':''?>" href="?pagina=cidades">Cidades</a><a class="<?= $pagina==='alterar_senha'?'ativo':''?>" href="?pagina=alterar_senha">Alterar senha</a></nav>
<div class="container"><?php if($mensagem):?><div class="alert success"><?=e($mensagem)?></div><?php endif;?><?php if($erro):?><div class="alert error"><?=e($erro)?></div><?php endif;?>

<?php if($pagina==="home"):?>
<div class="card"><h2>Bem-vindo ao CRUD Mundo</h2><p>Use o menu para cadastrar, consultar, editar e excluir informações.</p></div>
<div class="stats"><?php foreach(["Continentes"=>"continentes","Países"=>"paises","Cidades"=>"cidades","Governantes"=>"governantes"] as $n=>$t):$q=$conexao->query("SELECT COUNT(*) total FROM $t")->fetch_assoc()["total"];?><div class="stat"><?=e($n)?><strong><?=$q?></strong></div><?php endforeach;?></div>

<?php elseif($pagina==="continentes"):
$ed=(int)($_GET["editar"]??0);$r=$ed?$conexao->query("SELECT * FROM continentes WHERE id=$ed")->fetch_assoc():null;?>
<div class="card"><h2><?=$r?"Editar":"Cadastrar"?> Continente</h2><form method="post" class="grid"><input type="hidden" name="acao" value="<?=$r?'editar_continente':'cadastrar_continente'?>"><?php if($r):?><input type="hidden" name="id" value="<?=$r['id']?>"><?php endif;?><div><label>Nome</label><input name="nome" value="<?=e($r['nome']??'')?>" required></div><div><label>População</label><input type="number" name="populacao" value="<?=e($r['populacao']??'')?>" required></div><div><label>Área (km²)</label><input type="number" step=".01" name="area" value="<?=e($r['area']??'')?>" required></div><div><label>Total de países</label><input type="number" name="total_paises" value="<?=e($r['total_paises']??'')?>" required></div><div><button class="btn btn-success"><?= $r?"Atualizar":"Cadastrar"?></button></div></form></div>
<div class="card"><h2>Continentes cadastrados</h2><input class="search" placeholder="Pesquisar..." oninput="filtrar(this)"><table><tr><th>ID</th><th>Nome</th><th>População</th><th>Área</th><th>Países</th><th>Ações</th></tr><?php $q=$conexao->query("SELECT * FROM continentes ORDER BY nome");while($x=$q->fetch_assoc()):?><tr><td><?=$x['id']?></td><td><?=e($x['nome'])?></td><td><?=number_format($x['populacao'],0,",",".")?></td><td><?=num($x['area'])?></td><td><?=$x['total_paises']?></td><td class="actions"><a class="btn btn-warning" href="?pagina=continentes&editar=<?=$x['id']?>">Editar</a><a class="btn btn-danger" href="?pagina=continentes&excluir=<?=$x['id']?>&tipo=continente" onclick="return confirm('Excluir?')">Excluir</a></td></tr><?php endwhile;?></table></div>

<?php elseif($pagina==="governantes"):
$ed=(int)($_GET["editar"]??0);$r=$ed?$conexao->query("SELECT * FROM governantes WHERE id=$ed")->fetch_assoc():null;?>
<div class="card"><h2><?=$r?"Editar":"Cadastrar"?> Governante</h2><form method="post" class="grid"><input type="hidden" name="acao" value="<?=$r?'editar_governante':'cadastrar_governante'?>"><?php if($r):?><input type="hidden" name="id" value="<?=$r['id']?>"><?php endif;?><div><label>Nome</label><input name="nome" value="<?=e($r['nome']??'')?>" required></div><div><label>Partido político</label><input name="partido_politico" value="<?=e($r['partido_politico']??'')?>" required></div><div><label>Data de nascimento</label><input type="date" name="data_nascimento" value="<?=e($r['data_nascimento']??'')?>" required></div><div><label>Idade</label><input type="number" name="idade" value="<?=e($r['idade']??'')?>" required></div><div><label>Início do mandato</label><input type="date" name="inicio_mandato" value="<?=e($r['inicio_mandato']??'')?>" required></div><div><label>Fim do mandato</label><input type="date" name="fim_mandato" value="<?=e($r['fim_mandato']??'')?>" required></div><div><button class="btn btn-success"><?=$r?"Atualizar":"Cadastrar"?></button></div></form></div>
<div class="card"><h2>Governantes cadastrados</h2><input class="search" placeholder="Pesquisar..." oninput="filtrar(this)"><table><tr><th>ID</th><th>Nome</th><th>Partido</th><th>Nascimento</th><th>Idade</th><th>Início</th><th>Fim</th><th>Ações</th></tr><?php $q=$conexao->query("SELECT * FROM governantes ORDER BY nome");while($x=$q->fetch_assoc()):?><tr><td><?=$x['id']?></td><td><?=e($x['nome'])?></td><td><?=e($x['partido_politico'])?></td><td><?=dataBR($x['data_nascimento'])?></td><td><?=$x['idade']?></td><td><?=dataBR($x['inicio_mandato'])?></td><td><?=dataBR($x['fim_mandato'])?></td><td class="actions"><a class="btn btn-warning" href="?pagina=governantes&editar=<?=$x['id']?>">Editar</a><a class="btn btn-danger" href="?pagina=governantes&excluir=<?=$x['id']?>&tipo=governante" onclick="return confirm('Excluir?')">Excluir</a></td></tr><?php endwhile;?></table></div>

<?php elseif($pagina==="paises"):
$ed=(int)($_GET["editar"]??0);$r=$ed?$conexao->query("SELECT * FROM paises WHERE id=$ed")->fetch_assoc():null;$cs=$conexao->query("SELECT * FROM continentes ORDER BY nome");$gs=$conexao->query("SELECT * FROM governantes ORDER BY nome");?>
<div class="card"><h2><?=$r?"Editar":"Cadastrar"?> País</h2><form method="post" class="grid"><input type="hidden" name="acao" value="<?=$r?'editar_pais':'cadastrar_pais'?>"><?php if($r):?><input type="hidden" name="id" value="<?=$r['id']?>"><?php endif;?><div><label>Nome</label><input name="nome" value="<?=e($r['nome']??'')?>" required></div><div><label>Continente</label><select name="continente_id" required><option value="">Selecione</option><?php while($c=$cs->fetch_assoc()):?><option value="<?=$c['id']?>" <?=($r['continente_id']??0)==$c['id']?"selected":""?>><?=e($c['nome'])?></option><?php endwhile;?></select></div><div><label>População</label><input type="number" name="populacao" value="<?=e($r['populacao']??'')?>" required></div><div><label>Área</label><input type="number" step=".01" name="area" value="<?=e($r['area']??'')?>" required></div><div><label>Idioma</label><input name="idioma" value="<?=e($r['idioma']??'')?>" required></div><div><label>Governante</label><select name="governante_id" required><option value="">Selecione</option><?php while($g=$gs->fetch_assoc()):?><option value="<?=$g['id']?>" <?=($r['governante_id']??0)==$g['id']?"selected":""?>><?=e($g['nome'])?></option><?php endwhile;?></select></div><div><label>Clima</label><input name="clima" value="<?=e($r['clima']??'')?>" required></div><div><label>Regime político</label><input name="regime_politico" value="<?=e($r['regime_politico']??'')?>" required></div><div><label>Moeda</label><input name="moeda" value="<?=e($r['moeda']??'')?>" required></div><div><button class="btn btn-success"><?=$r?"Atualizar":"Cadastrar"?></button></div></form></div>
<div class="card"><h2>Países cadastrados</h2><input class="search" placeholder="Pesquisar..." oninput="filtrar(this)"><table><tr><th>ID</th><th>Nome</th><th>Continente</th><th>População</th><th>Idioma</th><th>Clima</th><th>Moeda</th><th>Ações</th></tr><?php $q=$conexao->query("SELECT p.*,c.nome continente FROM paises p LEFT JOIN continentes c ON c.id=p.continente_id ORDER BY p.nome");while($x=$q->fetch_assoc()):?><tr><td><?=$x['id']?></td><td><?=e($x['nome'])?></td><td><?=e($x['continente'])?></td><td><?=number_format($x['populacao'],0,",",".")?></td><td><?=e($x['idioma'])?></td><td><?=e($x['clima'])?></td><td><?=e($x['moeda'])?></td><td class="actions"><a class="btn btn-warning" href="?pagina=paises&editar=<?=$x['id']?>">Editar</a><a class="btn btn-danger" href="?pagina=paises&excluir=<?=$x['id']?>&tipo=pais" onclick="return confirm('Excluir país? As cidades vinculadas serão excluídas.')">Excluir</a></td></tr><?php endwhile;?></table></div>

<?php elseif($pagina==="alterar_senha"):?>
<div class="card" style="max-width:420px;margin:0 auto">
<h2>Alterar senha de acesso</h2>
<?php if($erroSenha):?><div class="alert error"><?=e($erroSenha)?></div><?php endif;?>
<?php if($mensagemSenha):?><div class="alert success"><?=e($mensagemSenha)?></div><?php endif;?>
<form method="post">
<input type="hidden" name="acao" value="alterar_senha">
<label>Senha atual</label><input type="password" name="senha_atual" required>
<label>Nova senha</label><input type="password" name="nova_senha" minlength="6" required>
<label>Confirmar nova senha</label><input type="password" name="confirmar_senha" minlength="6" required>
<button class="btn btn-success" style="margin-top:18px;width:100%">Salvar nova senha</button>
</form>
</div>

<?php elseif($pagina==="cidades"):
$ed=(int)($_GET["editar"]??0);$r=$ed?$conexao->query("SELECT * FROM cidades WHERE id=$ed")->fetch_assoc():null;$ps=$conexao->query("SELECT * FROM paises ORDER BY nome");$gs=$conexao->query("SELECT * FROM governantes ORDER BY nome");?>
<div class="card"><h2><?=$r?"Editar":"Cadastrar"?> Cidade</h2><form method="post" class="grid"><input type="hidden" name="acao" value="<?=$r?'editar_cidade':'cadastrar_cidade'?>"><?php if($r):?><input type="hidden" name="id" value="<?=$r['id']?>"><?php endif;?><div><label>Nome</label><input name="nome" value="<?=e($r['nome']??'')?>" required></div><div><label>País</label><select name="pais_id" required><option value="">Selecione</option><?php while($p=$ps->fetch_assoc()):?><option value="<?=$p['id']?>" <?=($r['pais_id']??0)==$p['id']?"selected":""?>><?=e($p['nome'])?></option><?php endwhile;?></select></div><div><label>População</label><input type="number" name="populacao" value="<?=e($r['populacao']??'')?>" required></div><div><label>Área</label><input type="number" step=".01" name="area" value="<?=e($r['area']??'')?>" required></div><div><label>Clima</label><input name="clima" value="<?=e($r['clima']??'')?>" required></div><div><label>Governante</label><select name="governante_id" required><option value="">Selecione</option><?php while($g=$gs->fetch_assoc()):?><option value="<?=$g['id']?>" <?=($r['governante_id']??0)==$g['id']?"selected":""?>><?=e($g['nome'])?></option><?php endwhile;?></select></div><div><label>Data de fundação</label><input type="date" name="data_fundacao" value="<?=e($r['data_fundacao']??'')?>" required></div><div><button class="btn btn-success"><?=$r?"Atualizar":"Cadastrar"?></button></div></form></div>
<div class="card"><h2>Cidades cadastradas</h2><input class="search" placeholder="Pesquisar..." oninput="filtrar(this)"><table><tr><th>ID</th><th>Nome</th><th>País</th><th>População</th><th>Área</th><th>Clima</th><th>Fundação</th><th>Ações</th></tr><?php $q=$conexao->query("SELECT c.*,p.nome pais FROM cidades c LEFT JOIN paises p ON p.id=c.pais_id ORDER BY c.nome");while($x=$q->fetch_assoc()):?><tr><td><?=$x['id']?></td><td><?=e($x['nome'])?></td><td><?=e($x['pais'])?></td><td><?=number_format($x['populacao'],0,",",".")?></td><td><?=num($x['area'])?></td><td><?=e($x['clima'])?></td><td><?=dataBR($x['data_fundacao'])?></td><td class="actions"><a class="btn btn-warning" href="?pagina=cidades&editar=<?=$x['id']?>">Editar</a><a class="btn btn-danger" href="?pagina=cidades&excluir=<?=$x['id']?>&tipo=cidade" onclick="return confirm('Excluir cidade?')">Excluir</a></td></tr><?php endwhile;?></table></div>
<?php endif;?></div><footer>CRUD Mundo - Programação Web</footer>
<script>function filtrar(i){let t=i.value.toLowerCase();i.parentElement.querySelectorAll("tbody tr").forEach(r=>r.style.display=r.innerText.toLowerCase().includes(t)?"":"none")}</script>
<?php endif; ?>
</body></html>
