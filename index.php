<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: views/inicio.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Alessandro">
    <meta name="description" content="Sistema de gestión SPA MYPE">
    <title>Aamaal Spa - Login</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <link rel="stylesheet" href="public/styles/index.css">
</head>

<body>
    <nav class="navbar">
        <div class="container-fluid position-absolute top-0 start-0">
            <a class="navbar-brand">Aamaal Spa's</a>
        </div>
    </nav>
    <div class="main-container">
        <div class="spa-image-section">
        </div>
        <div class="login-section">
            <div class="login-card">
                <div class="logo-container">
                    <div class="logo">
                        <img class="img-fluid" src="img/logoaamaalspa.jfif" alt="Logo Aamaal Spa">
                    </div>
                </div>
                <div class="login-header">
                    <h2 class="login-title">Iniciar Sesión</h2>
                </div>
                <form id="loginForm" method="POST" action="controllers/AuthController.php">
                    <div class="form-group">
                        <input type="text" name="username" id="username" class="form-control" placeholder="Ingresa tu usuario" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <div class="password-container">
                            <input type="password" name="password" class="form-control" placeholder="Ingrese su contraseña" id="password" required
                                autocomplete="current-password">
                            <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>
                    <div class="remember-forgot">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Recordar
                            </label>
                        </div>
                        <a href="#" class="forgot-link">¿Olvidó su contraseña?</a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="btnLogin">
                        <span class="btn-text">Ingresar</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                    <hr>
                </form>
            </div>
        </div>
    </div>
    
    <div class="container-fluid position-absolute bottom-0 start-0">
        <div>
            <a class="navbar-brand footer"></a>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            if (toggle && passwordInput) {
                toggle.style.cursor = 'pointer';
                toggle.addEventListener('click', function () {
                    const isPwd = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPwd ? 'text' : 'password');
                    toggle.classList.toggle('bi-eye');
                    toggle.classList.toggle('bi-eye-slash');
                });
            }
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();

                const btnLogin = $('#btnLogin');
                const btnText = btnLogin.find('.btn-text');
                const spinner = btnLogin.find('.spinner-border');
                
                const username = $('#username').val().trim();
                const password = $('#password').val();

                if (!username || !password) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campos incompletos',
                        text: 'Por favor ingrese usuario y contraseña'
                    });
                    return;
                }
                btnLogin.prop('disabled', true);
                btnText.text('Verificando...');
                spinner.removeClass('d-none');
                const urlDestino = $(this).attr('action'); 
                const formData = $(this).serialize() + '&action=login';

                $.ajax({
                    url: urlDestino, 
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Bienvenido!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de acceso',
                                text: response.message
                            });
                            btnLogin.prop('disabled', false);
                            btnText.text('Ingresar');
                            spinner.addClass('d-none');
                            $('#password').val('').focus();
                        }
                    },
                    
                    error: function(xhr, status, error) {
                        console.error("Error de Red AJAX:", xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'Error en el servidor'
                        });
                        btnLogin.prop('disabled', false);
                        btnText.text('Ingresar');
                        spinner.addClass('d-none');
                    }
                }); 
            }); 
        }); 
    </script>
</body>
</html>