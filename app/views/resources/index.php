<?php partial('group-header', ['group' => $group]); ?>

<div x-data="resourceUpload(<?= (int)$group['id'] ?>)" class="space-y-6">
    <section class="glass-card rounded-2xl p-6">
        <h2 class="text-lg font-bold mb-4">Upload Resource</h2>
        <p class="text-xs text-slate-400 mb-4">PDF or images only · Max 5MB</p>
        <form @submit.prevent="upload()" class="space-y-3">
            <input type="text" x-model="title" class="input" placeholder="Title" required>
            <div class="select-wrap">
                <select x-model="category" class="input">
                    <option value="notes">Notes</option>
                    <option value="slides">Slides</option>
                    <option value="assignments">Assignments</option>
                    <option value="reference">Reference</option>
                </select>
            </div>
            <input type="file" @change="file=$event.target.files[0]" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="input">
            <div x-show="uploading" class="progress-bar"><div class="progress-bar-fill" :style="'width:'+progress+'%'"></div></div>
            <button type="submit" class="btn-primary" :disabled="uploading">Upload</button>
        </form>
    </section>

    <section>
        <h2 class="text-lg font-bold mb-4">Shared Files</h2>
        <?php if (empty($resources)): ?>
        <div class="glass-card rounded-2xl p-8 text-center text-slate-400">No resources uploaded yet.</div>
        <?php else: ?>
        <div class="grid sm:grid-cols-2 gap-4" id="resource-list">
            <?php foreach ($resources as $r):
                $isImg = str_starts_with($r['mime_type'], 'image/');
            ?>
            <article class="glass-card rounded-2xl p-4 flex gap-4">
                <span class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center shrink-0">
                    <i data-lucide="<?= $isImg ? 'image' : 'file-text' ?>" class="w-6 h-6 text-indigo-500"></i>
                </span>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold truncate"><?= e($r['title']) ?></h3>
                    <p class="text-xs text-slate-400"><?= e($r['uploader_name']) ?> · <?= strtoupper(pathinfo($r['original_name'], PATHINFO_EXTENSION)) ?> · <?= round($r['file_size']/1024) ?> KB</p>
                    <span class="badge bg-slate-500/10 text-slate-500 mt-1"><?= e($r['category']) ?></span>
                    <div class="flex gap-2 mt-3">
                        <a href="<?= url('/resources/' . $r['id'] . '/download') ?>" class="btn-secondary text-xs py-1">Download</a>
                        <?php if ($isImg): ?>
                        <a href="<?= url('uploads/resources/' . $r['filename']) ?>" target="_blank" class="btn-secondary text-xs py-1">Preview</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<script>
function resourceUpload(groupId) {
    return {
        groupId, title: '', category: 'notes', file: null, uploading: false, progress: 0,
        async upload() {
            if (!this.file) return;
            this.uploading = true;
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            fd.append('title', this.title || this.file.name);
            fd.append('category', this.category);
            fd.append('file', this.file);
            const xhr = new XMLHttpRequest();
            xhr.upload.onprogress = (e) => { if (e.lengthComputable) this.progress = (e.loaded/e.total)*100; };
            xhr.onload = () => {
                this.uploading = false;
                if (xhr.status === 200) { showToast('Uploaded'); location.reload(); }
                else showToast(JSON.parse(xhr.responseText).error || 'Upload failed', 'error');
            };
            xhr.open('POST', `${window.APP_URL}/groups/${groupId}/resources`);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);
            xhr.send(fd);
        },
    };
}
</script>
