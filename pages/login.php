<?php
session_start();
include '../includes/cadastro/cadastro.php'; // arquivo de cadastro

// Mensagens
$mensagemSucesso = isset($_SESSION['mensagemSucesso']) ? $_SESSION['mensagemSucesso'] : '';
$mensagemErro = '';

// Limpa a mensagem de sucesso após exibição
if ($mensagemSucesso) {
    unset($_SESSION['mensagemSucesso']);
}

// Função para validar a senha
function validarSenha($senha) {
    // Define os requisitos mínimos para a senha
    $minimoCaracteres = 8;
    $requerMaiuscula = true;
    $requerMinuscula = true;
    $requerNumero = true;
    $requerEspecial = true;

    // Verificar a complexidade da senha
    $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%#*?&])[A-Za-z\d@$!%*?&]{' . $minimoCaracteres . ',}$/';

    // Verifica se a senha atende à expressão regular
    if (!preg_match($regex, $senha)) {
        return "A senha deve ter pelo menos 8 caracteres, incluindo letras maiúsculas e minúsculas, números e um caractere especial.";
    }

    // Verifica se a senha não contém o nome de usuário
    if (isset($_POST['nome']) && stripos($senha, $_POST['nome']) !== false) {
        return "A senha não pode conter o nome do usuário.";
    }

    // Se todas as verificações passarem, a senha é válida
    return true;
}

// Cadastro de usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar'])) {
    $nome = $_POST['nome'];
    $sobrenome = $_POST['sobrenome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $cpf = $_POST['cpf'];
    $senha = $_POST['senha'];
    $confirmarSenha = $_POST['confirmarSenha'];

    // Verifica se as senhas são as mesmas
    if ($senha !== $confirmarSenha) {
        $mensagemErro = "Você digitou senhas diferentes.";
    } else {
        // Valida a senha
        $resultadoValidacao = validarSenha($senha);
        if ($resultadoValidacao !== true) {
            $mensagemErro = $resultadoValidacao; // Exibe a mensagem de erro específica retornada pela função
        } else {
            // Hash da senha e inserção no banco de dados
            $hashedSenha = password_hash($senha, PASSWORD_DEFAULT);

            // Insere os dados no banco de dados
            $pdo = Conexao::getConexao();
            $stmt = $pdo->prepare("INSERT INTO usuario (nome, sobrenome, email, telefone, cpf, senha) VALUES (?, ?, ?, ?, ?, ?)");

            if ($stmt->execute([$nome, $sobrenome, $email, $telefone, $cpf, $hashedSenha])) {
                $_SESSION['mensagemSucesso'] = "Parabéns! seu cadastro foi realizado com sucesso!";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit; // Redireciona para a mesma página
            } else {
                $mensagemErro = "Erro ao cadastrar. Tente novamente.";
            }
        } 
    }
}

// Login de usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['entrar'])) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $pdo = Conexao::getConexao();
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['user_id'] = $usuario['id'];
        header('Location: ../home.php'); 
        exit;
    } else {
        $mensagemErro = "Usuário ou senha inválidos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/login.css">
    <title>Login</title>
</head>
<body>
    <div class="container" id="container">
        <div class="form-container sign-up">
            <form method="post" onsubmit="return validarFormulario()">
                <h1 class="criar-conta">Criar conta</h1>
                <span class="loader"></span>             
                <input type="text" id="nome" name="nome" placeholder="Nome" required>
                <span id="mensagemErroNome" style="color: red;"></span>
                <input type="text" id="sobrenome" name="sobrenome" placeholder="Sobrenome" required>
                <span id="mensagemErroSobrenome" style="color: red;"></span>
                <input type="text" id="cpf" name="cpf" placeholder="CPF" required maxlength="14">
                <input type="email" id="email" name="email" placeholder="Email" required>
                <input type="email" id="confirmarEmail" name="confirmarEmail" placeholder="Confirmar email" required>
                <input type="tel" id="telefone" name="telefone" placeholder="Telefone" required maxlength="15">
                <input type="password" name="senha" placeholder="Senha" required>
                <input type="password" name="confirmarSenha" placeholder="Confirmar senha" required>    
                <div class="termos">
                    <input type="checkbox" id="termos" required>
                    <label for="termos">Aceito os <a href="#">termos e condições</a></label>
                </div>
                
                <span></span>
                <button class="cadastrar" type="submit" name="cadastrar">Cadastrar</button>
                
                <?php if ($mensagemErro): ?>
                    <div style="color: red;">
                        <span>
                        <?= htmlspecialchars($mensagemErro) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="form-container sign-in">
            <form method="post">
                <!-- Mensagem de cadastro realizado com sucesso -->
                <?php if ($mensagemSucesso): ?>
                    <span class="mensagemsucesso">
                        <?= htmlspecialchars($mensagemSucesso) ?>
                    </span>
                <?php endif; ?>

                <h1>Serv+Cuscuz</h1>
                <span>Seja bem-vindo</span>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="senha" placeholder="Senha" required>
                
                <?php if ($mensagemErro): ?>
                    <div style="color: red;"><?= htmlspecialchars($mensagemErro) ?></div>
                <?php endif; ?>

                <a href="#">Esqueceu sua senha?</a>
                <button class="cadastrar" type="submit" name="entrar">Entrar</button>
            </form>
        </div>
        
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Bem-vindo de volta!</h1>
                    <p>Ops, já sou registrado</p>
                    <button class="hidden" id="login">Voltar</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Olá, amigo!</h1>
                    <p>Cadastre-se com seus dados pessoais para usar todos os recursos do site</p>
                    <button class="hidden" id="register">Inscrever-se</button>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/mascaras.js" defer></script>
    <script src="../js/login.js"></script>
</body>
</html>