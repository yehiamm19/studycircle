function kanban(groupId, initialColumns, opts = {}) {
    const moscowLabels = {
        must: 'Must',
        should: 'Should',
        could: 'Could',
        wont: "Won't",
    };

    return {
        groupId,
        columns: initialColumns,
        sortDisabled: !!opts.sortDisabled,
        sprints: opts.sprints || [],
        requirements: opts.requirements || [],
        showCreate: false,
        activeTask: null,
        commentBody: '',
        requirementEditIds: [],
        form: {
            title: '',
            description: '',
            priority: 'medium',
            moscow_priority: 'could',
            story_points: 1,
            sprint_id: '',
            label: 'homework',
            due_date: '',
            assignee_id: '',
            requirement_ids: [],
        },

        init() {
            this.$nextTick(() => this.setupSortable());
        },

        moscowLabel(m) {
            return moscowLabels[m] || moscowLabels.could;
        },

        parseReqIds(task) {
            if (!task) {
                return [];
            }
            const csv = task.requirement_ids_csv;
            if (csv && String(csv).trim() !== '') {
                return String(csv)
                    .split(',')
                    .map((x) => parseInt(x, 10))
                    .filter((n) => !Number.isNaN(n) && n > 0);
            }
            const li = task.linked_requirement_ids;
            if (Array.isArray(li)) {
                return li.map((n) => parseInt(n, 10)).filter((n) => n > 0);
            }

            return [];
        },

        setupSortable() {
            ['todo', 'in_progress', 'completed'].forEach((status) => {
                const el = document.getElementById('col-' + status);
                if (!el) return;
                const inst = el._sortable;
                if (inst) inst.destroy();
                el._sortable = new Sortable(el, {
                    group: 'kanban',
                    animation: 220,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    disabled: this.sortDisabled,
                    onEnd: (evt) => this.onDrop(evt, status),
                });
            });
        },

        async onDrop(evt, newStatus) {
            if (this.sortDisabled) return;
            const col = document.getElementById('col-' + newStatus);
            const ids = [...col.querySelectorAll('[data-id]')].map((el) => parseInt(el.dataset.id, 10));
            const taskId = parseInt(evt.item.dataset.id, 10);
            try {
                await api(`${window.APP_URL}/groups/${this.groupId}/tasks/reorder`, {
                    method: 'POST',
                    body: JSON.stringify({ status: newStatus, task_ids: ids, _csrf: CSRF }),
                });
                const fd = new FormData();
                fd.append('_csrf', CSRF);
                fd.append('status', newStatus);
                const data = await api(`${window.APP_URL}/tasks/${taskId}`, { method: 'POST', body: fd });
                handleAchievements(data);
                this.syncColumnsFromDom();
                if (newStatus === 'completed') showToast('Task completed! +XP');
            } catch (e) {
                showToast(e.message, 'error');
                location.reload();
            }
        },

        syncColumnsFromDom() {
            const cols = { todo: [], in_progress: [], completed: [] };
            ['todo', 'in_progress', 'completed'].forEach((status) => {
                const el = document.getElementById('col-' + status);
                [...el.querySelectorAll('[data-id]')].forEach((node) => {
                    const id = parseInt(node.dataset.id, 10);
                    const task = this.findTask(id);
                    if (task) cols[status].push({ ...task, status });
                });
            });
            this.columns = cols;
        },

        findTask(id) {
            for (const s of ['todo', 'in_progress', 'completed']) {
                const t = (this.columns[s] || []).find((x) => x.id == id);
                if (t) return t;
            }
            return null;
        },

        async createTask() {
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            ['title', 'description', 'priority', 'moscow_priority', 'label', 'due_date'].forEach((k) =>
                fd.append(k, this.form[k] ?? ''),
            );
            fd.append('story_points', String(this.form.story_points ?? 1));
            fd.append('sprint_id', this.form.sprint_id || '');
            fd.append('assignee_id', this.form.assignee_id || '');
            (this.form.requirement_ids || []).forEach((rid) => fd.append('requirement_ids[]', rid));
            try {
                const data = await api(`${window.APP_URL}/groups/${this.groupId}/tasks`, { method: 'POST', body: fd });
                const t = data.task || {};
                this.columns.todo.push(t);
                this.showCreate = false;
                this.form = {
                    title: '',
                    description: '',
                    priority: 'medium',
                    moscow_priority: 'could',
                    story_points: 1,
                    sprint_id: '',
                    label: 'homework',
                    due_date: '',
                    assignee_id: '',
                    requirement_ids: [],
                };
                showToast('Task created');
                this.initSortable();
            } catch (e) {
                showToast(e.message, 'error');
            }
        },

        openTask(task) {
            this.requirementEditIds = this.parseReqIds(task);
            this.activeTask = {
                ...task,
                sprint_id: task.sprint_id != null && task.sprint_id !== '' ? String(task.sprint_id) : '',
                assignee_id: task.assignee_id != null && task.assignee_id !== '' ? String(task.assignee_id) : '',
                moscow_priority: task.moscow_priority || 'could',
                story_points: task.story_points != null ? task.story_points : 1,
            };
            this.loadComments();
        },

        isReqChecked(id) {
            return this.requirementEditIds.map(Number).includes(Number(id));
        },

        toggleReq(id, on) {
            id = Number(id);
            let next = [...this.requirementEditIds.map(Number)].filter((n) => n > 0);
            if (on) {
                if (!next.includes(id)) next.push(id);
            } else {
                next = next.filter((n) => n !== id);
            }
            this.requirementEditIds = next;
        },

        async loadComments() {
            if (!this.activeTask) return;
            const data = await api(`${window.APP_URL}/tasks/${this.activeTask.id}/comments`);
            const box = document.getElementById('task-comments');
            if (!box) return;
            box.innerHTML =
                (data.comments || []).map((c) => `<p class="text-xs"><strong>${c.name}:</strong> ${c.body}</p>`).join('') ||
                '<p class="text-xs text-slate-400">No comments yet</p>';
        },

        async addComment() {
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            fd.append('body', this.commentBody);
            await api(`${window.APP_URL}/tasks/${this.activeTask.id}/comments`, { method: 'POST', body: fd });
            this.commentBody = '';
            this.loadComments();
        },

        async saveTask() {
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            ['title', 'description', 'priority', 'label', 'due_date', 'status', 'moscow_priority'].forEach((k) => {
                if (this.activeTask[k] != null) fd.append(k, this.activeTask[k]);
            });
            fd.append('story_points', String(this.activeTask.story_points ?? 1));
            fd.append('sprint_id', this.activeTask.sprint_id || '');
            fd.append('assignee_id', this.activeTask.assignee_id || '');
            fd.append('sync_requirements', '1');
            this.requirementEditIds.forEach((rid) => fd.append('requirement_ids[]', rid));
            try {
                const data = await api(`${window.APP_URL}/tasks/${this.activeTask.id}`, { method: 'POST', body: fd });
                handleAchievements(data);
                this.activeTask = null;
                location.reload();
            } catch (e) {
                showToast(e.message, 'error');
            }
        },

        async deleteTask() {
            if (!confirm('Delete this task?')) return;
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            await api(`${window.APP_URL}/tasks/${this.activeTask.id}/delete`, { method: 'POST', body: fd });
            this.activeTask = null;
            location.reload();
        },

        initSortable() {
            this.$nextTick(() => this.setupSortable());
        },
    };
}
