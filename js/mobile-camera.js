/**
 * NextERP Mobile Camera
 *
 * Kept in an external file because Nextcloud's CSP blocks inline JavaScript.
 */
(function () {
	'use strict';

	function initCamera() {
		const root = document.getElementById('camera-root');
		if (!root) {
			return;
		}

		const video = document.getElementById('camera-video');
		const canvas = document.getElementById('camera-canvas');
		const open = document.getElementById('camera-open');
		const shutter = document.getElementById('camera-shutter');
		const cancel = document.getElementById('camera-cancel');
		const retry = document.getElementById('camera-retry');
		const preview = document.getElementById('camera-preview');
		const idle = document.getElementById('camera-idle');
		const save = document.getElementById('camera-save');
		const file = document.getElementById('camera-file');
		const fileLabel = document.getElementById('camera-file-label');
		const status = document.getElementById('camera-status');
		const desc = document.getElementById('camera-description');

		if (!video || !canvas || !open || !shutter || !cancel || !retry ||
			!preview || !idle || !save || !file || !fileLabel || !status || !desc) {
			return;
		}

		const uploadUrl = root.dataset.uploadUrl || '';
		const requestToken = root.dataset.requesttoken || root.dataset.requestToken || '';
		let stream = null;
		let blob = null;
		let objectUrl = null;
		let uploading = false;

		function message(text, bad = false) {
			status.textContent = text;
			status.style.display = 'block';
			status.style.background = bad ? '#fff1f1' : '#eef5ff';
			status.style.color = bad ? '#9b1c1c' : '#174c90';
		}

		function stopCamera() {
			if (stream) {
				stream.getTracks().forEach((track) => track.stop());
				stream = null;
			}
			video.srcObject = null;
		}

		function resetView() {
			stopCamera();
			video.style.display = 'none';
			preview.style.display = 'none';
			idle.style.display = 'grid';
			open.style.display = 'grid';
			shutter.style.display = 'none';
			cancel.style.display = 'none';
			retry.style.display = 'none';
			fileLabel.style.display = 'grid';
			save.style.display = 'none';
		}

		async function startCamera() {
			if (!window.isSecureContext) {
				message('Kamera benötigt HTTPS.', true);
				return;
			}

			if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
				message('Live-Kamera wird von diesem Browser nicht unterstützt.', true);
				return;
			}

			stopCamera();
			status.style.display = 'none';

			try {
				stream = await navigator.mediaDevices.getUserMedia({
					audio: false,
					video: {
						facingMode: { ideal: 'environment' },
						width: { ideal: 1920 },
						height: { ideal: 1080 }
					}
				});

				video.srcObject = stream;
				await video.play();

				idle.style.display = 'none';
				preview.style.display = 'none';
				video.style.display = 'block';
				open.style.display = 'none';
				fileLabel.style.display = 'none';
				retry.style.display = 'none';
				save.style.display = 'none';
				shutter.style.display = 'grid';
				cancel.style.display = 'grid';
			} catch (error) {
				const detail = error && (error.message || error.name) ? (error.message || error.name) : 'unbekannt';
				message('Kamera konnte nicht gestartet werden: ' + detail, true);
			}
		}

		function showBlob(newBlob) {
			blob = newBlob;
			stopCamera();

			if (objectUrl) {
				URL.revokeObjectURL(objectUrl);
			}
			objectUrl = URL.createObjectURL(newBlob);
			preview.src = objectUrl;

			video.style.display = 'none';
			idle.style.display = 'none';
			preview.style.display = 'block';
			open.style.display = 'none';
			shutter.style.display = 'none';
			cancel.style.display = 'none';
			fileLabel.style.display = 'none';
			retry.style.display = 'grid';
			save.style.display = 'block';
		}

		shutter.addEventListener('click', function () {
			if (!video.videoWidth || !video.videoHeight) {
				message('Kamerabild ist noch nicht bereit.', true);
				return;
			}

			let width = video.videoWidth;
			let height = video.videoHeight;
			const scale = Math.min(1, 1920 / Math.max(width, height));
			width = Math.round(width * scale);
			height = Math.round(height * scale);

			canvas.width = width;
			canvas.height = height;
			const ctx = canvas.getContext('2d', { alpha: false });
			ctx.drawImage(video, 0, 0, width, height);

			canvas.toBlob(function (newBlob) {
				if (!newBlob) {
					message('Foto konnte nicht erzeugt werden.', true);
					return;
				}
				showBlob(newBlob);
			}, 'image/jpeg', 0.88);
		});

		async function uploadPhoto() {
			if (!blob || uploading) {
				return;
			}
			if (!uploadUrl) {
				message('Upload-Ziel fehlt.', true);
				return;
			}

			uploading = true;
			save.disabled = true;
			save.textContent = 'Speichere …';
			message('Foto wird im Projekt gespeichert …');

			try {
				const formData = new FormData();
				formData.append('requesttoken', requestToken);
				formData.append('description', desc.value || '');
				formData.append('document', blob, 'Foto-' + Date.now() + '.jpg');

				const response = await fetch(uploadUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin',
					headers: {
						'requesttoken': requestToken,
						'Accept': 'application/json'
					}
				});

				let data = {};
				try {
					data = await response.json();
				} catch (ignore) {
					// handled below through HTTP status
				}

				if (!response.ok || data.ok !== true) {
					throw new Error(data.message || ('Serverfehler ' + response.status));
				}

				message('Foto gespeichert.');
				setTimeout(function () {
					window.location.reload();
				}, 600);
			} catch (error) {
				uploading = false;
				save.disabled = false;
				save.textContent = 'Foto zum Projekt speichern';
				message(error && error.message ? error.message : 'Speichern fehlgeschlagen.', true);
			}
		}

		open.addEventListener('click', startCamera);
		retry.addEventListener('click', startCamera);
		cancel.addEventListener('click', resetView);
		save.addEventListener('click', uploadPhoto);

		file.addEventListener('change', function () {
			const selected = file.files && file.files[0] ? file.files[0] : null;
			if (!selected) {
				return;
			}
			showBlob(selected);
			message('Datei ausgewählt. Jetzt speichern.');
		});

		window.addEventListener('pagehide', function () {
			stopCamera();
			if (objectUrl) {
				URL.revokeObjectURL(objectUrl);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initCamera, { once: true });
	} else {
		initCamera();
	}
})();
