<?php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "bd_mundo";
$conexao = mysqli_connect($host, $usuario, $senha, $banco);
if (!$conexao) {
    die("Erro ao conectar ao banco: " . mysqli_connect_error());
}
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'home';
if (isset($_POST['cadastrar_continente'])) {

    $nome = mysqli_real_escape_string($conexao, $_POST['nome']);
    $populacao = $_POST['populacao'];
    $area = $_POST['area'];
    $total = $_POST['total_paises'];

    $sql = "INSERT INTO continentes
            (nome,populacao,area,total_paises)
            VALUES
            ('$nome','$populacao','$area','$total')";

    mysqli_query($conexao, $sql);

    header("Location: index.php?pagina=continentes");
    exit;
}
$erro_exclusao = "";
if (isset($_GET['excluir_continente'])) {

    $id = (int)$_GET['excluir_continente'];

    if (mysqli_query($conexao, "DELETE FROM continentes WHERE id=$id")) {
        header("Location: index.php?pagina=continentes");
        exit;
    } else {
        $erro_exclusao = "Não é possível excluir este continente: existem países vinculados a ele.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

<meta charset="UTF-8">

<title>CRUD Mundo</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}
body{
    background:#f4f4f4;
}
header{
    background:#003366;
    color:white;
    padding:20px;
    text-align:center;
}
nav{
    display:flex;
    justify-content:center;
    gap:15px;
    flex-wrap:wrap;
    background:#00509e;
    padding:15px;
}
nav a{
    color:white;
    text-decoration:none;
    padding:10px 18px;
    border-radius:5px;
    background:#0066cc;
    transition:.3s;
}
nav a:hover{
    background:#003366;
}
.container{
    width:90%;
    max-width:1100px;
    margin:30px auto;
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 0 10px rgba(0,0,0,.15);
}
table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}
table th{
    background:#003366;
    color:white;
}
table td,
table th{
    padding:10px;
    border:1px solid #ccc;
}
input,
select{
    width:100%;
    padding:10px;
    margin-bottom:12px;
}
button{
    padding:10px 20px;
    border:none;
    cursor:pointer;
    border-radius:5px;
}
.btnSalvar{
    background:#28a745;
    color:white;
}
.btnEditar{
    background:#ffc107;
    color:black;
    text-decoration:none;
    padding:6px 10px;
}
.btnExcluir{
    background:#dc3545;
    color:white;
    text-decoration:none;
    padding:6px 10px;
}
</style>

</head>

<body>

<header>
<h1>CRUD MUNDO</h1>
<p>Sistema de gerenciamento de Continentes, Países, Cidades e Governantes</p>
</header>

<nav>
<a href="index.php">Início</a>
<a href="index.php?pagina=continentes">Continentes</a>
<a href="index.php?pagina=governantes">Governantes</a>
<a href="index.php?pagina=paises">Países</a>
<a href="index.php?pagina=cidades">Cidades</a>
</nav>

<div class="container">

<?php

if($pagina=="home"){
    echo "<h2>Bem-vindo ao CRUD Mundo</h2>";
    echo "<br>";
    echo "<p>Escolha uma opção no menu para começar.</p>";
}
elseif($pagina=="continentes"){

    if($erro_exclusao != ""){
        echo "<p style='color:#dc3545;font-weight:bold;'>".$erro_exclusao."</p>";
    }

    if(isset($_GET['editar_continente'])){
        $id = (int)$_GET['editar_continente'];
        $dados = mysqli_fetch_assoc(mysqli_query($conexao,
        "SELECT * FROM continentes WHERE id=$id"));

        if(!$dados){
            echo "<p style='color:#dc3545;font-weight:bold;'>Continente não encontrado.</p>";
        }else{
?>

<h2>Editar Continente</h2>

<form method="POST">
    <input type="hidden" name="id" value="<?=$dados['id']?>">
    <input
        type="text"
        name="nome"
        value="<?=$dados['nome']?>"
        required>
    <input
        type="number"
        name="populacao"
        value="<?=$dados['populacao']?>"
        required>
    <input
        type="number"
        step="0.01"
        name="area"
        value="<?=$dados['area']?>"
        required>
    <input
        type="number"
        name="total_paises"
        value="<?=$dados['total_paises']?>"
        required>
    <button
        class="btnSalvar"
        name="atualizar_continente">
        Atualizar
    </button>
</form>

<?php
        }
    }else{
?>

<h2>Novo Continente</h2>

<form method="POST">
    <input
        type="text"
        name="nome"
        placeholder="Nome"
        required>
    <input
        type="number"
        name="populacao"
        placeholder="População"
        required>
    <input
        type="number"
        step="0.01"
        name="area"
        placeholder="Área (km²)"
        required>
    <input
        type="number"
        name="total_paises"
        placeholder="Total de países"
        required>
    <button
        class="btnSalvar"
        name="cadastrar_continente">
        Cadastrar
    </button>
</form>

<?php
    }
    if(isset($_POST['atualizar_continente'])){
        $id = $_POST['id'];
        $nome = mysqli_real_escape_string($conexao,$_POST['nome']);
        $populacao = $_POST['populacao'];
        $area = $_POST['area'];
        $total = $_POST['total_paises'];
        mysqli_query($conexao,"
            UPDATE continentes SET
            nome='$nome',
            populacao='$populacao',
            area='$area',
            total_paises='$total'
            WHERE id=$id
        ");
        echo "<script>
        location='index.php?pagina=continentes';
        </script>";
    }
?>

<h2>Continentes Cadastrados</h2>

<table>

<tr>
<th>ID</th>
<th>Nome</th>
<th>População</th>
<th>Área</th>
<th>Total Países</th>
<th>Ações</th>
</tr>

<?php
$sql = mysqli_query($conexao,"
SELECT * FROM continentes
ORDER BY nome
");
while($c=mysqli_fetch_assoc($sql)){
?>

<tr>
<td><?=$c['id']?></td>
<td><?=$c['nome']?></td>
<td><?=number_format($c['populacao'],0,",",".")?></td>
<td><?=number_format($c['area'],2,",",".")?></td>
<td><?=$c['total_paises']?></td>
<td>

<a
class="btnEditar"
href="index.php?pagina=continentes&editar_continente=<?=$c['id']?>">
Editar
</a>

<a
class="btnExcluir"
onclick="return confirm('Excluir continente?')"
href="index.php?pagina=continentes&excluir_continente=<?=$c['id']?>">
Excluir
</a>
</td>
</tr>

<?php
}
?>

</table>

<?php
}
elseif($pagina=="governantes"){
    if(isset($_GET['editar_governante'])){
        $id = (int)$_GET['editar_governante'];
        $dados = mysqli_fetch_assoc(mysqli_query($conexao,
        "SELECT * FROM governantes WHERE id=$id"));

        if(!$dados){
            echo "<p style='color:#dc3545;font-weight:bold;'>Governante não encontrado.</p>";
        }else{
?>

<h2>Editar Governante</h2>

<form method="POST">
<input type="hidden" name="id" value="<?=$dados['id']?>">
<input type="text" name="nome" value="<?=$dados['nome']?>" required>
<input type="text" name="partido_politico" value="<?=$dados['partido_politico']?>" required>
<input type="date" name="data_nascimento" value="<?=$dados['data_nascimento']?>" required>
<input type="number" name="idade" value="<?=$dados['idade']?>" required>
<input type="date" name="inicio_mandato" value="<?=$dados['inicio_mandato']?>" required>
<input type="date" name="fim_mandato" value="<?=$dados['fim_mandato']?>" required>
<button class="btnSalvar" name="atualizar_governante">Atualizar</button>
</form>

<?php
        }
    }else{
?>

<h2>Novo Governante</h2>

<form method="POST">
<input type="text" name="nome" placeholder="Nome" required>
<input type="text" name="partido_politico" placeholder="Partido Político" required>
<input type="date" name="data_nascimento" required>
<input type="number" name="idade" placeholder="Idade" required>
<input type="date" name="inicio_mandato" required>
<input type="date" name="fim_mandato" required>
<button class="btnSalvar" name="cadastrar_governante">Cadastrar</button>
</form>

<?php
    }
    if(isset($_POST['cadastrar_governante'])){
        $nome=mysqli_real_escape_string($conexao,$_POST['nome']);
        $partido=mysqli_real_escape_string($conexao,$_POST['partido_politico']);
        $nascimento=$_POST['data_nascimento'];
        $idade=$_POST['idade'];
        $inicio=$_POST['inicio_mandato'];
        $fim=$_POST['fim_mandato'];
        mysqli_query($conexao,"
        INSERT INTO governantes
        (nome,partido_politico,data_nascimento,idade,inicio_mandato,fim_mandato)
        VALUES
        ('$nome','$partido','$nascimento','$idade','$inicio','$fim')
        ");
        echo "<script>location='index.php?pagina=governantes';</script>";
    }

    if(isset($_POST['atualizar_governante'])){
        $id=$_POST['id'];
        $nome=mysqli_real_escape_string($conexao,$_POST['nome']);
        $partido=mysqli_real_escape_string($conexao,$_POST['partido_politico']);
        $nascimento=$_POST['data_nascimento'];
        $idade=$_POST['idade'];
        $inicio=$_POST['inicio_mandato'];
        $fim=$_POST['fim_mandato'];
        mysqli_query($conexao,"
        UPDATE governantes SET
        nome='$nome',
        partido_politico='$partido',
        data_nascimento='$nascimento',
        idade='$idade',
        inicio_mandato='$inicio',
        fim_mandato='$fim'
        WHERE id=$id
        ");
        echo "<script>location='index.php?pagina=governantes';</script>";
    }
    $erro_exclusao_gov = "";
    if(isset($_GET['excluir_governante'])){
        $id=(int)$_GET['excluir_governante'];
        if(mysqli_query($conexao, "DELETE FROM governantes WHERE id=$id")){
            echo "<script>location='index.php?pagina=governantes';</script>";
        }else{
            $erro_exclusao_gov = "Não é possível excluir este governante: existem países ou cidades vinculados a ele.";
        }
    }
?>

<?php if($erro_exclusao_gov != ""){ ?>
<p style="color:#dc3545;font-weight:bold;"><?=$erro_exclusao_gov?></p>
<?php } ?>

<h2>Governantes Cadastrados</h2>

<table>
<tr>
<th>ID</th>
<th>Nome</th>
<th>Partido</th>
<th>Nascimento</th>
<th>Idade</th>
<th>Início</th>
<th>Fim</th>
<th>Ações</th>
</tr>

<?php
$sql=mysqli_query($conexao,"
SELECT * FROM governantes
ORDER BY nome
");
while($g=mysqli_fetch_assoc($sql)){
?>

<tr>
<td><?=$g['id']?></td>
<td><?=$g['nome']?></td>
<td><?=$g['partido_politico']?></td>
<td><?=date('d/m/Y',strtotime($g['data_nascimento']))?></td>
<td><?=$g['idade']?></td>
<td><?=date('d/m/Y',strtotime($g['inicio_mandato']))?></td>
<td><?=date('d/m/Y',strtotime($g['fim_mandato']))?></td>
<td>

<a class="btnEditar"
href="index.php?pagina=governantes&editar_governante=<?=$g['id']?>">
Editar
</a>

<a class="btnExcluir"
onclick="return confirm('Excluir governante?')"
href="index.php?pagina=governantes&excluir_governante=<?=$g['id']?>">
Excluir
</a>

</td>

</tr>

<?php
}
?>

</table>

<?php

}
elseif($pagina=="paises"){
    if(isset($_GET['editar_pais'])){
        $id=(int)$_GET['editar_pais'];
        $dados=mysqli_fetch_assoc(mysqli_query($conexao,"
        SELECT * FROM paises WHERE id=$id
        "));

        if(!$dados){
            echo "<p style='color:#dc3545;font-weight:bold;'>País não encontrado.</p>";
        }else{
?>

<h2>Editar País</h2>

<form method="POST">
<input type="hidden" name="id" value="<?=$dados['id']?>">
<input type="text" name="nome" value="<?=$dados['nome']?>" required>
<select name="continente_id" required>
<option value="">Selecione</option>

<?php
$c=mysqli_query($conexao,"SELECT * FROM continentes ORDER BY nome");
while($cont=mysqli_fetch_assoc($c)){
?>

<option
value="<?=$cont['id']?>"
<?=$cont['id']==$dados['continente_id']?"selected":""?>>
<?=$cont['nome']?>
</option>

<?php
}
?>
</select>
<input type="number" name="populacao" value="<?=$dados['populacao']?>" required>
<input type="number" step="0.01" name="area" value="<?=$dados['area']?>" required>
<input type="text" name="idioma" value="<?=$dados['idioma']?>" required>
<select name="governante_id" required>
<option value="">Selecione</option>
<?php
$g=mysqli_query($conexao,"SELECT * FROM governantes ORDER BY nome");
while($gov=mysqli_fetch_assoc($g)){
?>

<option
value="<?=$gov['id']?>"
<?=$gov['id']==$dados['governante_id']?"selected":""?>>
<?=$gov['nome']?>
</option>

<?php
}
?>

</select>
<input type="text" name="clima" value="<?=$dados['clima']?>" required>
<input type="text" name="regime_politico" value="<?=$dados['regime_politico']?>" required>
<input type="text" name="moeda" value="<?=$dados['moeda']?>" required>
<button class="btnSalvar" name="atualizar_pais">
Atualizar
</button>
</form>

<?php
        }
}else{
?>

<h2>Novo País</h2>

<form method="POST">
<input type="text" name="nome" placeholder="Nome" required>
<select name="continente_id" required>
<option value="">Selecione o continente</option>

<?php
$c=mysqli_query($conexao,"SELECT * FROM continentes ORDER BY nome");
while($cont=mysqli_fetch_assoc($c)){
?>
<option value="<?=$cont['id']?>">
<?=$cont['nome']?>
</option>

<?php
}
?>

</select>
<input type="number" name="populacao" placeholder="População" required>
<input type="number" step="0.01" name="area" placeholder="Área (km²)" required>
<input type="text" name="idioma" placeholder="Idioma" required>
<select name="governante_id" required>
<option value="">Selecione o governante</option>

<?php
$g=mysqli_query($conexao,"SELECT * FROM governantes ORDER BY nome");
while($gov=mysqli_fetch_assoc($g)){
?>

<option value="<?=$gov['id']?>">
<?=$gov['nome']?>
</option>

<?php
}
?>

</select>
<input type="text" name="clima" placeholder="Clima" required>
<input type="text" name="regime_politico" placeholder="Regime Político" required>
<input type="text" name="moeda" placeholder="Moeda" required>
<button class="btnSalvar" name="cadastrar_pais">
Cadastrar
</button>
</form>

<?php
}
if(isset($_POST['cadastrar_pais'])){
$nome=mysqli_real_escape_string($conexao,$_POST['nome']);
$continente=$_POST['continente_id'];
$populacao=$_POST['populacao'];
$area=$_POST['area'];
$idioma=mysqli_real_escape_string($conexao,$_POST['idioma']);
$governante=$_POST['governante_id'];
$clima=mysqli_real_escape_string($conexao,$_POST['clima']);
$regime=mysqli_real_escape_string($conexao,$_POST['regime_politico']);
$moeda=mysqli_real_escape_string($conexao,$_POST['moeda']);
mysqli_query($conexao,"
INSERT INTO paises
(nome,continente_id,populacao,area,idioma,governante_id,clima,regime_politico,moeda)
VALUES
('$nome','$continente','$populacao','$area','$idioma','$governante','$clima','$regime','$moeda')
");
echo "<script>location='index.php?pagina=paises';</script>";
}
if(isset($_POST['atualizar_pais'])){
$id=$_POST['id'];
$nome=mysqli_real_escape_string($conexao,$_POST['nome']);
$continente=$_POST['continente_id'];
$populacao=$_POST['populacao'];
$area=$_POST['area'];
$idioma=mysqli_real_escape_string($conexao,$_POST['idioma']);
$governante=$_POST['governante_id'];
$clima=mysqli_real_escape_string($conexao,$_POST['clima']);
$regime=mysqli_real_escape_string($conexao,$_POST['regime_politico']);
$moeda=mysqli_real_escape_string($conexao,$_POST['moeda']);
mysqli_query($conexao,"
UPDATE paises SET
nome='$nome',
continente_id='$continente',
populacao='$populacao',
area='$area',
idioma='$idioma',
governante_id='$governante',
clima='$clima',
regime_politico='$regime',
moeda='$moeda'
WHERE id=$id
");
echo "<script>location='index.php?pagina=paises';</script>";
}
if(isset($_GET['excluir_pais'])){
$id=(int)$_GET['excluir_pais'];
mysqli_query($conexao,"DELETE FROM paises WHERE id=$id");
echo "<script>location='index.php?pagina=paises';</script>";
}
?>
<h2>Países Cadastrados</h2>
<table>
<tr>
<th>ID</th>
<th>Nome</th>
<th>Continente</th>
<th>População</th>
<th>Idioma</th>
<th>Moeda</th>
<th>Ações</th>
</tr>

<?php
$sql=mysqli_query($conexao,"
SELECT
paises.*,
continentes.nome AS continente
FROM paises
INNER JOIN continentes
ON continentes.id=paises.continente_id
ORDER BY paises.nome
");
while($p=mysqli_fetch_assoc($sql)){
?>

<tr>
<td><?=$p['id']?></td>
<td><?=$p['nome']?></td>
<td><?=$p['continente']?></td>
<td><?=number_format($p['populacao'],0,",",".")?></td>
<td><?=$p['idioma']?></td>
<td><?=$p['moeda']?></td>
<td>
<a class="btnEditar"
href="index.php?pagina=paises&editar_pais=<?=$p['id']?>">
Editar
</a>

<a class="btnExcluir"
onclick="return confirm('Excluir país?')"
href="index.php?pagina=paises&excluir_pais=<?=$p['id']?>">
Excluir
</a>
</td>
</tr>

<?php
}
?>
</table>

<?php
}
elseif($pagina=="cidades"){
    if(isset($_GET['editar_cidade'])){
        $id=(int)$_GET['editar_cidade'];
        $dados=mysqli_fetch_assoc(mysqli_query($conexao,"
        SELECT * FROM cidades WHERE id=$id
        "));

        if(!$dados){
            echo "<p style='color:#dc3545;font-weight:bold;'>Cidade não encontrada.</p>";
        }else{
?>

<h2>Editar Cidade</h2>
<form method="POST">
<input type="hidden" name="id" value="<?=$dados['id']?>">
<input type="text" name="nome" value="<?=$dados['nome']?>" required>
<select name="pais_id" required>
<option value="">Selecione o país</option>

<?php
$p=mysqli_query($conexao,"SELECT * FROM paises ORDER BY nome");
while($pais=mysqli_fetch_assoc($p)){
?>

<option
value="<?=$pais['id']?>"
<?=$pais['id']==$dados['pais_id']?"selected":""?>>
<?=$pais['nome']?>
</option>

<?php
}
?>

</select>

<input type="number" name="populacao" value="<?=$dados['populacao']?>" required>
<input type="number" step="0.01" name="area" value="<?=$dados['area']?>" required>
<input type="text" name="clima" value="<?=$dados['clima']?>" required>
<select name="governante_id" required>
<option value="">Selecione o governante</option>

<?php
$g=mysqli_query($conexao,"SELECT * FROM governantes ORDER BY nome");
while($gov=mysqli_fetch_assoc($g)){
?>

<option
value="<?=$gov['id']?>"
<?=$gov['id']==$dados['governante_id']?"selected":""?>>
<?=$gov['nome']?>
</option>

<?php
}
?>

</select>

<input type="date" name="data_fundacao" value="<?=$dados['data_fundacao']?>" required>
<button class="btnSalvar" name="atualizar_cidade">
Atualizar
</button>
</form>

<?php
        }
}else{
?>

<h2>Nova Cidade</h2>
<form method="POST">
<input type="text" name="nome" placeholder="Nome" required>
<select name="pais_id" required>
<option value="">Selecione o país</option>

<?php
$p=mysqli_query($conexao,"SELECT * FROM paises ORDER BY nome");
while($pais=mysqli_fetch_assoc($p)){
?>

<option value="<?=$pais['id']?>">
<?=$pais['nome']?>
</option>

<?php
}
?>

</select>
<input type="number" name="populacao" placeholder="População" required>
<input type="number" step="0.01" name="area" placeholder="Área (km²)" required>
<input type="text" name="clima" placeholder="Clima" required>
<select name="governante_id" required>
<option value="">Selecione o governante</option>

<?php
$g=mysqli_query($conexao,"SELECT * FROM governantes ORDER BY nome");
while($gov=mysqli_fetch_assoc($g)){
?>

<option value="<?=$gov['id']?>">
<?=$gov['nome']?>
</option>

<?php
}
?>

</select>

<input type="date" name="data_fundacao" required>
<button class="btnSalvar" name="cadastrar_cidade">
Cadastrar
</button>
</form>

<?php
}
if(isset($_POST['cadastrar_cidade'])){
$nome=mysqli_real_escape_string($conexao,$_POST['nome']);
$pais=$_POST['pais_id'];
$populacao=$_POST['populacao'];
$area=$_POST['area'];
$clima=mysqli_real_escape_string($conexao,$_POST['clima']);
$governante=$_POST['governante_id'];
$data=$_POST['data_fundacao'];
mysqli_query($conexao,"
INSERT INTO cidades
(nome,pais_id,populacao,area,clima,governante_id,data_fundacao)
VALUES
('$nome','$pais','$populacao','$area','$clima','$governante','$data')
");
echo "<script>location='index.php?pagina=cidades';</script>";
}
if(isset($_POST['atualizar_cidade'])){
$id=$_POST['id'];
$nome=mysqli_real_escape_string($conexao,$_POST['nome']);
$pais=$_POST['pais_id'];
$populacao=$_POST['populacao'];
$area=$_POST['area'];
$clima=mysqli_real_escape_string($conexao,$_POST['clima']);
$governante=$_POST['governante_id'];
$data=$_POST['data_fundacao'];
mysqli_query($conexao,"
UPDATE cidades SET
nome='$nome',
pais_id='$pais',
populacao='$populacao',
area='$area',
clima='$clima',
governante_id='$governante',
data_fundacao='$data'
WHERE id=$id
");
echo "<script>location='index.php?pagina=cidades';</script>";
}
if(isset($_GET['excluir_cidade'])){
$id=(int)$_GET['excluir_cidade'];
mysqli_query($conexao,"
DELETE FROM cidades
WHERE id=$id
");
echo "<script>location='index.php?pagina=cidades';</script>";
}
?>

<h2>Cidades Cadastradas</h2>

<table>
<tr>
<th>ID</th>
<th>Nome</th>
<th>País</th>
<th>População</th>
<th>Clima</th>
<th>Fundação</th>
<th>Ações</th>
</tr>

<?php
$sql=mysqli_query($conexao,"
SELECT
cidades.*,
paises.nome AS pais
FROM cidades
INNER JOIN paises
ON paises.id=cidades.pais_id
ORDER BY cidades.nome
");
while($c=mysqli_fetch_assoc($sql)){
?>

<tr>
<td><?=$c['id']?></td>
<td><?=$c['nome']?></td>
<td><?=$c['pais']?></td>
<td><?=number_format($c['populacao'],0,",",".")?></td>
<td><?=$c['clima']?></td>
<td><?=date('d/m/Y',strtotime($c['data_fundacao']))?></td>
<td>
<a
class="btnEditar"
href="index.php?pagina=cidades&editar_cidade=<?=$c['id']?>">
Editar
</a>
<a
class="btnExcluir"
onclick="return confirm('Excluir cidade?')"
href="index.php?pagina=cidades&excluir_cidade=<?=$c['id']?>">
Excluir
</a>
</td>
</tr>

<?php
}
?>
</table>

<?php
}
?>
</div>

<script>
const formularios=document.querySelectorAll("form");
formularios.forEach(form=>{
form.addEventListener("submit",function(e){
const campos=form.querySelectorAll("input[required],select[required]");
for(let campo of campos){
if(campo.value.trim()==""){
alert("Preencha todos os campos.");
campo.focus();
e.preventDefault();
return;
}
}
});
});
</script>
</body>
</html>