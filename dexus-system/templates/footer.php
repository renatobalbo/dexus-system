</main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/api.js"></script>
    <script src="/assets/js/validation.js"></script>
    
    <?php if (isset($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
    <script src="<?php echo $script; ?>"></script>
    <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inlineScript)): ?>
    <script>
    <?php echo $inlineScript; ?>
    </script>
    <?php endif; ?>
    
    <!-- Modal de Loader -->
    <div class="modal fade" id="loader-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loader-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <h5 id="loader-message">Processando, aguarde...</h5>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Função para exibir loader
    function showLoader(message = 'Processando, aguarde...') {
        document.getElementById('loader-message').textContent = message;
        const loaderModal = new bootstrap.Modal(document.getElementById('loader-modal'));
        loaderModal.show();
        return loaderModal;
    }
    
    // Função para ocultar loader
    function hideLoader() {
        const loaderModal = bootstrap.Modal.getInstance(document.getElementById('loader-modal'));
        if (loaderModal) {
            loaderModal.hide();
        }
    }
    </script>
</body>
</html>