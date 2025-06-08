<?php
// ===========================================
// ARQUIVO: includes/event-card.php
// Componente reutilizável para cards de eventos
// ===========================================
// Este arquivo deve ser salvo em: includes/event-card.php
?>

<div class="card event-card position-relative">
    <div class="event-badge">
        <span class="price-badge <?php echo isset($evento['evento_gratuito']) && $evento['evento_gratuito'] ? '' : 'paid'; ?>">
            <?php 
            if (isset($evento['preco_formatado'])) {
                echo $evento['preco_formatado'];
            } elseif (isset($evento['evento_gratuito']) && $evento['evento_gratuito']) {
                echo 'Gratuito';
            } elseif (isset($evento['preco']) && $evento['preco'] > 0) {
                echo 'R$ ' . number_format($evento['preco'], 2, ',', '.');
            } else {
                echo 'Gratuito';
            }
            ?>
        </span>
    </div>
    
    <?php if (!empty($evento['imagem_capa'])): ?>
        <?php 
        $imagemUrl = isset($evento['imagem_url']) ? $evento['imagem_url'] : 
                    (strpos($evento['imagem_capa'], 'http') === 0 ? $evento['imagem_capa'] : 
                     'uploads/eventos/' . $evento['imagem_capa']); 
        ?>
        <img src="<?php echo htmlspecialchars($imagemUrl); ?>" 
             alt="<?php echo htmlspecialchars($evento['titulo'] ?? 'Evento'); ?>"
             class="event-image"
             loading="lazy"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="no-image" style="display: none;">
            <i class="fas fa-calendar-alt fa-3x"></i>
        </div>
    <?php else: ?>
        <div class="no-image">
            <i class="fas fa-calendar-alt fa-3x"></i>
        </div>
    <?php endif; ?>
    
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="card-title"><?php echo htmlspecialchars($evento['titulo'] ?? 'Título não disponível'); ?></h5>
            <?php if (!empty($evento['nome_categoria'])): ?>
                <span class="category-badge">
                    <?php echo htmlspecialchars($evento['nome_categoria']); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <p class="card-text text-muted">
            <?php 
            $descricao = $evento['descricao'] ?? 'Descrição não disponível';
            echo substr(htmlspecialchars($descricao), 0, 100) . (strlen($descricao) > 100 ? '...' : '');
            ?>
        </p>
        
        <div class="mb-3">
            <small class="text-muted">
                <i class="fas fa-calendar me-1"></i>
                <?php 
                if (isset($evento['data_inicio_formatada'])) {
                    echo $evento['data_inicio_formatada'];
                } elseif (isset($evento['data_inicio'])) {
                    echo date('d/m/Y', strtotime($evento['data_inicio']));
                } else {
                    echo 'Data não informada';
                }
                ?>
                
                <i class="fas fa-clock ms-3 me-1"></i>
                <?php 
                if (isset($evento['horario_inicio_formatado'])) {
                    echo $evento['horario_inicio_formatado'];
                } elseif (isset($evento['horario_inicio'])) {
                    echo date('H:i', strtotime($evento['horario_inicio']));
                } else {
                    echo 'Horário não informado';
                }
                ?>
            </small>
            <br>
            <small class="text-muted">
                <i class="fas fa-map-marker-alt me-1"></i>
                <?php echo htmlspecialchars($evento['local_cidade'] ?? 'Local não informado'); ?>
                
                <i class="fas fa-users ms-3 me-1"></i>
                <?php echo (int)($evento['total_inscritos'] ?? 0); ?> inscritos
            </small>
        </div>
        
        <div class="d-grid">
            <a href="views/events/view.php?id=<?php echo (int)($evento['id_evento'] ?? 0); ?>" 
               class="btn btn-primary"
               aria-label="Ver detalhes do evento <?php echo htmlspecialchars($evento['titulo'] ?? ''); ?>">
                <i class="fas fa-eye me-2"></i>Ver Detalhes
            </a>
        </div>
    </div>
</div>