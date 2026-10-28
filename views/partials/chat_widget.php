<?php
/**
 * Widget flotante del chatbot.
 *
 * Se incluye desde views/layout/footer.php.
 */
$chatHistory = $_SESSION['chat_history'] ?? [];
if (!is_array($chatHistory)) {
    $chatHistory = [];
}
$chatHistory = array_values(array_filter($chatHistory, static function ($item): bool {
    return is_array($item) && isset($item['content']) && is_string($item['content']);
}));
$chatHistory = array_slice($chatHistory, -10);

$e = static fn ($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');

$enlacesDe = static function (array $item): array {
    $enlaces = [];
    foreach (['links', 'extra_links'] as $clave) {
        if (!isset($item[$clave]) || !is_array($item[$clave])) {
            continue;
        }
        foreach ($item[$clave] as $enlace) {
            if (!is_array($enlace) || !isset($enlace['url'], $enlace['label'])) {
                continue;
            }
            $url = trim((string) $enlace['url']);
            $label = trim((string) $enlace['label']);
            if ($url !== '' && $label !== '') {
                $enlaces[] = ['url' => $url, 'label' => $label];
            }
        }
    }
    return $enlaces;
};
?>
<div id="chatbot"
     class="chatbot"
     data-token-url="?ruta=chat/token"
     data-message-url="?ruta=chat/message">

  <button class="chatbot-toggle"
          id="chatbot-toggle"
          type="button"
          aria-label="Abrir chat de ayuda"
          aria-controls="chatbot-panel"
          aria-expanded="false">
    <span class="chatbot-icon" aria-hidden="true">💬</span>
    <span class="chatbot-pulse" aria-hidden="true"></span>
  </button>

  <div class="chatbot-panel"
       id="chatbot-panel"
       role="dialog"
       aria-label="Chat de ayuda de Jeremy-Bot"
       hidden>

    <!-- Cabecera -->
    <div class="chatbot-header">
      <div class="chatbot-avatar" aria-hidden="true">⚡</div>
      <div class="chatbot-header-text">
        <strong>Jeremy-Bot</strong>
        <small><span class="chatbot-dot" aria-hidden="true"></span> En línea · responde al instante</small>
      </div>
      <button class="chatbot-close"
              id="chatbot-close"
              type="button"
              aria-label="Cerrar chat">✕</button>
    </div>

    <!-- Mensajes + botones rápidos al final del scroll -->
    <div class="chatbot-messages" id="chatbot-messages" aria-live="polite">
      <?php if ($chatHistory === []): ?>
        <div class="chatbot-bubble bot">
          ¡Hola! 👋 Soy <strong>Jeremy-Bot</strong>, el asistente de Jeremy-Tech.<br>
          Puedo ayudarte con tus pedidos, productos, envíos y más. ¿Qué necesitas?
        </div>
      <?php else: ?>
        <?php foreach ($chatHistory as $item): ?>
          <?php $esBot = ($item['role'] ?? 'user') === 'assistant'; ?>
          <div class="chatbot-bubble <?= $esBot ? 'bot' : 'user' ?>"><?= $e($item['content']) ?></div>
          <?php $enlacesItem = $enlacesDe($item); ?>
          <?php if ($enlacesItem !== []): ?>
            <div class="chatbot-links">
              <?php foreach ($enlacesItem as $enlace): ?>
                <?php $externo = preg_match('#^https?://#i', $enlace['url']) === 1; ?>
                <a href="<?= $e($enlace['url']) ?>"
                   <?= $externo ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                  <?= $e($enlace['label']) ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- Botones rápidos dentro del área de mensajes -->
      <div class="chatbot-quick" id="chatbot-quick"></div>
    </div>

    <!-- Formulario de entrada -->
    <form class="chatbot-input" id="chatbot-form" autocomplete="off">
      <label class="chatbot-sr-only" for="chatbot-input">Escribe tu mensaje</label>
      <input id="chatbot-input"
             name="message"
             type="text"
             maxlength="1000"
             placeholder="Escribe tu mensaje..."
             autocomplete="off"/>
      <button id="chatbot-send" type="submit" aria-label="Enviar mensaje">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
          <path d="M3 20.5 21 12 3 3.5l4.5 7.5L3 20.5Z" fill="currentColor"/>
        </svg>
      </button>
    </form>

    <!-- Pie -->
    <small class="chatbot-footer">🔒 Chat seguro · Jeremy-Tech</small>

  </div>
</div>
