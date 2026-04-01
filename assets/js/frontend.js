(function () {
	if (typeof window.teamopsHubWorkspace === 'undefined') {
		return;
	}

	const board = document.querySelector('[data-teamops-kanban]');

	if (!board) {
		return;
	}

	let draggedCard = null;

	const setBusy = (isBusy) => {
		board.classList.toggle('is-busy', isBusy);
	};

	board.querySelectorAll('.teamops-front-kanban-card').forEach((card) => {
		card.addEventListener('dragstart', () => {
			draggedCard = card;
			card.classList.add('is-dragging');
		});

		card.addEventListener('dragend', () => {
			card.classList.remove('is-dragging');
			draggedCard = null;
		});
	});

	board.querySelectorAll('.teamops-front-kanban-dropzone').forEach((zone) => {
		zone.addEventListener('dragover', (event) => {
			event.preventDefault();
			zone.classList.add('is-over');
		});

		zone.addEventListener('dragleave', () => {
			zone.classList.remove('is-over');
		});

		zone.addEventListener('drop', async (event) => {
			event.preventDefault();
			zone.classList.remove('is-over');

			if (!draggedCard) {
				return;
			}

			const taskId = draggedCard.dataset.taskId;
			const status = zone.dataset.statusKey;
			const currentStatus = draggedCard.dataset.statusKey;

			if (!taskId || !status || status === currentStatus) {
				return;
			}

			const body = new URLSearchParams();
			body.append('action', 'teamops_hub_front_move_task');
			body.append('task_id', taskId);
			body.append('status', status);
			body.append('nonce', window.teamopsHubWorkspace.kanbanNonce);

			setBusy(true);

			try {
				const response = await fetch(window.teamopsHubWorkspace.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
					},
					body: body.toString()
				});

				const result = await response.json();

				if (!response.ok || !result.success) {
					throw new Error(result?.data?.message || window.teamopsHubWorkspace.kanbanError);
				}

				window.location.reload();
			} catch (error) {
				window.alert(error.message || window.teamopsHubWorkspace.kanbanError);
			} finally {
				setBusy(false);
			}
		});
	});
})();
