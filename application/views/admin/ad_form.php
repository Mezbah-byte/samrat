<a href="<?php echo base_url('admin/ads'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All ads</a>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-badge-ad"></i> <?php echo $mode === 'edit' ? 'Edit Ad' : 'New Ad'; ?></div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open_multipart($mode === 'edit' ? 'admin/ads/edit/'.$a->id : 'admin/ads/create'); ?>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" value="<?php echo set_value('title', $a->title); ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Type</label>
              <select name="type" class="form-select">
                <?php foreach (array('image', 'video', 'banner', 'link') as $t): ?>
                  <option value="<?php echo $t; ?>" <?php echo $a->type === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Placement</label>
              <select name="placement" class="form-select">
                <option value="daily_task" <?php echo $a->placement === 'daily_task' ? 'selected' : ''; ?>>Daily task (counts toward quota)</option>
                <option value="global" <?php echo $a->placement === 'global' ? 'selected' : ''; ?>>Global (display only)</option>
              </select>
              <div class="form-text">Only daily-task ads unlock profit.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Watch Seconds <span class="text-danger">*</span></label>
              <input type="number" min="0" name="watch_seconds" class="form-control" value="<?php echo set_value('watch_seconds', $a->watch_seconds); ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Sort Order</label>
              <input type="number" min="0" name="sort_order" class="form-control" value="<?php echo set_value('sort_order', $a->sort_order); ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Creative Source</label>
              <select name="source" class="form-select" id="adSource">
                <option value="upload" <?php echo $a->source === 'upload' ? 'selected' : ''; ?>>Upload / file URL &mdash; your own image or video</option>
                <option value="embed"  <?php echo $a->source === 'embed'  ? 'selected' : ''; ?>>Ad network tag &mdash; paste the network's HTML/JS</option>
                <option value="vast"   <?php echo $a->source === 'vast'   ? 'selected' : ''; ?>>VAST video tag &mdash; network video, played to the end</option>
              </select>
              <div class="form-text">
                A VAST ad only clears the user's quota when the network's video actually finishes. Network tags run
                inside a sandboxed iframe, so their script cannot touch the session.
              </div>
            </div>

            <div class="col-12 src-upload">
              <label class="form-label">Media URL</label>
              <input type="url" name="media_url" class="form-control" value="<?php echo set_value('media_url', $a->media_url); ?>" placeholder="https://cdn.example.com/spot.mp4">
              <div class="form-text">Use this for a hosted video (set Type to <strong>Video</strong>) or a remote image. Leave empty if you upload a file below.</div>
            </div>

            <div class="col-12 src-vast">
              <label class="form-label">VAST / VPAID Tag URL</label>
              <input type="url" name="vast_url" class="form-control" value="<?php echo set_value('vast_url', $a->vast_url); ?>" placeholder="https://your-network.example/vast?zone=123">
              <div class="form-text">
                Any VAST 2/3/4 tag: AdSense for video, Ad Manager, SpotX, Adsterra VAST, Monetag, ExoClick&hellip;
                Google's public test tag is on the sample ad shipped with the installer.
              </div>
            </div>

            <div class="col-12 src-embed">
              <label class="form-label">Network Tag (HTML / JavaScript)</label>
              <textarea name="embed_code" class="form-control font-monospace" rows="6" spellcheck="false" placeholder="&lt;script src=&quot;//network.example/tag.js&quot;&gt;&lt;/script&gt;"><?php echo set_value('embed_code', $a->embed_code); ?></textarea>
              <div class="form-text">
                Pasted verbatim &mdash; the network's own code. It is rendered in a sandboxed iframe with no
                same-origin access. Only paste tags from a network you trust.
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Target URL</label>
              <input type="url" name="target_url" class="form-control" value="<?php echo set_value('target_url', $a->target_url); ?>" placeholder="https://...">
              <div class="form-text">Optional &ldquo;Visit advertiser&rdquo; link. Network and VAST creatives carry their own click-through.</div>
            </div>
            <div class="col-12">
              <label class="form-label">Body Text</label>
              <textarea name="body" class="form-control" rows="3"><?php echo set_value('body', $a->body); ?></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Starts On</label>
              <input type="date" name="starts_at" class="form-control" value="<?php echo set_value('starts_at', $a->starts_at); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Ends On</label>
              <input type="date" name="ends_at" class="form-control" value="<?php echo set_value('ends_at', $a->ends_at); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" <?php echo $a->status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $a->status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
            <div class="col-12 src-upload">
              <label class="form-label">Media Image</label>
              <input type="file" name="media" class="form-control" accept="image/*">
              <?php if ($a->media): ?>
                <img src="<?php echo upload_url('ads', $a->media); ?>" class="mt-2 rounded" style="max-height:130px" alt="">
              <?php endif; ?>
            </div>
          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Changes' : 'Create Ad'; ?></button>
          <a href="<?php echo base_url('admin/ads'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>

        <?php /* Only the fields the chosen source uses stay on screen. */ ?>
        <script>
        (function () {
          var sel = document.getElementById('adSource');
          if (!sel) return;

          function sync() {
            ['upload', 'embed', 'vast'].forEach(function (name) {
              document.querySelectorAll('.src-' + name).forEach(function (el) {
                el.hidden = sel.value !== name;
              });
            });
          }

          sel.addEventListener('change', sync);
          sync();
        })();
        </script>
      </div>
    </div>
  </div>
</div>
