<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Dexus' : 'Sistema de Gestão Dexus'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (isset($printMode) && $printMode): ?>
    <link rel="stylesheet" href="/assets/css/print.css">
    <?php endif; ?>
    <?php if (isset($extraStyles)): ?>
    <?php foreach ($extraStyles as $style): ?>
    <link rel="stylesheet" href="<?php echo $style; ?>">
    <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Mensagens de alerta -->
    <div class="alert-container position-fixed top-0 end-0 p-3"></div>
    
    <!-- Conteúdo -->
    <div class="container-fluid">
        <div class="row">
            <?php if (!isset($hideSidebar) || !$hideSidebar): ?>
            <!-- Sidebar -->
            <?php include_once __DIR__ . '/menu.php'; ?>
            
            <!-- Conteúdo principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php else: ?>
            <!-- Conteúdo principal (tela cheia) -->
            <main class="col-12 px-md-4">
            <?php endif; ?>
                
                <!-- Cabeçalho da página -->
                <?php if (isset($pageTitle) && !isset($hidePageHeader)): ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $pageTitle; ?></h1>
                    <?php if (isset($pageActions)): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php echo $pageActions; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>