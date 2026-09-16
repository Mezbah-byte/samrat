<?php
/**
 * Row of headline tiles, shared by every agent screen.
 *
 * Expects $tiles: a list of associative rows.
 *
 *   label  string   caption above the figure          (required)
 *   value  mixed    the figure                        (required)
 *   icon   string   lucide icon name                  (required)
 *   grad   string   grad-primary | grad-success | ... (required)
 *   money  bool     format as currency, default TRUE
 *   note   string   footer line, optional
 *   link   string   makes the whole tile a link, optional
 *   tone   string   extra class on the value, optional (text-warn, text-bad)
 *
 * Optional $tiles_offset shifts the entrance animation so a row placed lower
 * on the page still cascades in the right order.
 */
$cols   = isset($tiles_cols) ? $tiles_cols : 'col-6 col-xl-3';
$offset = isset($tiles_offset) ? (int) $tiles_offset : 1;
?>
<div class="row g-3 mb-3">
  <?php foreach ($tiles as $i => $t): ?>
    <?php
      $is_money = ! array_key_exists('money', $t) || $t['money'];
      $link     = isset($t['link']) ? $t['link'] : NULL;
      $tone     = isset($t['tone']) ? $t['tone'] : '';
    ?>
    <div class="<?php echo $cols; ?>">
      <?php if ($link): ?><a href="<?php echo base_url($link); ?>" class="text-decoration-none d-block h-100"><?php endif; ?>
        <div class="panel <?php echo $link ? 'lift' : ''; ?> stat h-100 reveal" data-reveal-order="<?php echo $offset + $i; ?>">
          <div class="stat-top">
            <div>
              <div class="stat-label"><?php echo html_escape($t['label']); ?></div>
              <div class="stat-value num <?php echo $tone; ?>"
                   data-count="<?php echo (float) $t['value']; ?>"
                   <?php if ($is_money): ?>data-count-prefix="<?php echo html_escape(currency()); ?>"<?php endif; ?>>
                <?php echo $is_money ? money($t['value']) : (int) $t['value']; ?>
              </div>
            </div>
            <span class="icon-tile <?php echo $t['grad']; ?>"><i data-lucide="<?php echo $t['icon']; ?>"></i></span>
          </div>
          <?php if ( ! empty($t['note']) || $link): ?>
            <div class="stat-foot">
              <span><?php echo isset($t['note']) ? html_escape($t['note']) : ''; ?></span>
              <?php if ($link): ?><i data-lucide="arrow-right"></i><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php if ($link): ?></a><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
