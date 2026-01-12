<?php
  if(!isset($_SESSION))
    session_start();    


?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Riquadro al Centro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style2.css">
  <style>
    body {
      background-color: #000000;
      margin: 0;
      padding: 0;
    }
    
    .form-control {
      background-color: #1a1a1a;
      border: 1px solid #8b00ff;
      color: #ffffff;
      border-radius: 10px;
      padding: 12px 20px;
    }
    
    .form-control::placeholder {
      color: #b8b8b89a;
      opacity: 1;
    }
    
    .form-control:focus {
      background-color: #1a1a1a;
      border-color: #8b00ff;
      color: #ffffff;
      box-shadow: 0 0 15px rgba(139,0,255,0.5);
    }
    
    .btn-accedi {
      background-color: #8b00ff;
      border: none;
      color: white;
      padding: 12px 40px;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s;
    }
    
    .btn-accedi:hover {
      background-color: #a020f0;
      box-shadow: 0 0 20px rgba(139,0,255,0.6);
    }
    
    .btn-registrati {
      background-color: transparent;
      border: 2px solid #8b00ff;
      color: #8b00ff;
      padding: 12px 40px;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s;
    }
    
    .btn-registrati:hover {
      background-color: rgba(139,0,255,0.1);
      box-shadow: 0 0 20px rgba(139,0,255,0.4);
    }
    
    .logo-placeholder {
      width: 60px;
      height: 60px;
      background-color: #8b00ff;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
    }
    .logo-placeholder img {
      object-fit: contain; /* oppure "cover" se vuoi riempire tutto */
      border-radius: 10px; /* opzionale, per seguire il bordo del contenitore */
    }

  </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
  <div class="position-relative d-flex flex-column justify-content-center align-items-center" 
       style="width:1000px; 
              height:800px; 
              background:#000000; 
              border-radius: 120px;
              border: 2px solid #8b00ff;
              box-shadow: 
                0 0 30px 5px rgba(139,0,255,0.8),
                0 0 60px 15px rgba(139,0,255,0.6),
                0 0 100px 30px rgba(139,0,255,0.4),
                inset 0 0 30px 5px rgba(139,0,255,0.3);
              padding: 60px;
       ">
    
    <!-- Logo e Nome -->
    <div class="d-flex align-items-center mb-5">
      <div class="logo-placeholder me-3 mb-5">
        <img src="img/logo.png" alt="">
      </div>
      <!-- Form<h1 class="text-white m-0" style="font-size: 3rem; font-weight: 700;">Clonefy</h1> -->
    </div>
    
    <!-- Messaggi di errore/successo -->
    <?php if(isset($_GET['error'])): ?>
      <?php 
      $errors = [
        'dati_mancanti' => 'Tutti i campi sono obbligatori.',
        'username_esistente' => 'Username già in uso.',
        'email_esistente' => 'Email già registrata.',
        'errore_generico' => 'Errore durante la registrazione. Riprova.',
        'password_corta' => 'La password deve essere di almeno 6 caratteri.'
      ];
      ?>
      <div class="alert alert-danger mb-4" style="width: 500px; color: #fff3f3ff;">
        <?php echo $errors[$_GET['error']] ?? 'Errore sconosciuto.'; ?>
      </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['success'])): ?>
      <div class="alert alert-success mb-4" style="width: 500px;">
        Registrazione completata con successo! Ora puoi effettuare il login.
      </div>
    <?php endif; ?>
    
    <!-- Form -->
    <form action="checkSignup.php" method="POST">
      <div style="width: 500px;">
        <div class="mb-4">
          <input name="nomeUtente" type="text" class="form-control" placeholder="Nome utente" required
                 value="<?php echo htmlspecialchars($_GET['username'] ?? ''); ?>">
        </div>
        <div class="mb-4">
          <input name="email" type="email" class="form-control" placeholder="Email" required
                 value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
        </div>
        <div class="mb-4">
          <input name="password" type="password" class="form-control" placeholder="Password" required>
          <small class="text-muted mt-1 d-block">La password deve essere di almeno 6 caratteri.</small>
        </div>
        <div class="d-flex gap-3 mt-4">
          <button class="btn btn-accedi flex-fill" type="submit" name="azione" value="signup">Registrati</button>
          <button class="btn btn-registrati flex-fill" type="button" onclick="window.location.href='login.php'">Accedi</button>
        </div>
      </div>
    </form>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  
</body>
</html>