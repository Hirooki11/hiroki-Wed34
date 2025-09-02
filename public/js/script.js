const input = document.getElementById('image_file'); // <input id="image_file" name="image">
if (input) {
  const MAX_BYTES = 5 * 1024 * 1024; // 5MB
  const MAX_DIM   = 3000;            // 長辺上限
  const MIN_Q     = 0.5;             // 最低品質
  const STEP      = 0.05;            // 品質調整刻み

  input.addEventListener('change', async () => {
    const f = input.files && input.files[0];
    if (!f || !f.type.startsWith('image/')) return;
    if (f.size <= MAX_BYTES) return;

    try {
      const resized = await downscaleToUnder5MB(f, MAX_BYTES, MAX_DIM, MIN_Q, STEP);
      if (resized && resized.size < f.size) {
        const dt = new DataTransfer();
        dt.items.add(resized);
        input.files = dt.files;
      }
    } catch (e) {
      console.warn('自動縮小に失敗:', e); // 失敗してもサーバ側で5MB超過は弾く想定
    }
  });
}

async function downscaleToUnder5MB(file, MAX_BYTES, MAX_DIM, MIN_Q, STEP) {
  const img = await loadImageFromFile(file);

  function scaledSize(img, maxDim) {
    const w = img.naturalWidth || img.width;
    const h = img.naturalHeight || img.height;
    const scale = Math.min(1, maxDim / Math.max(w, h));
    return { w: Math.max(1, Math.round(w * scale)), h: Math.max(1, Math.round(h * scale)) };
  }

  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d');

  let { w, h } = scaledSize(img, MAX_DIM);
  let quality = 0.9;
  let blob;

  for (let attempt = 0; attempt < 3; attempt++) {
    canvas.width = w;
    canvas.height = h;
    ctx.drawImage(img, 0, 0, w, h);

    blob = await canvasToBlob(canvas, 'image/jpeg', quality);
    while (blob.size > MAX_BYTES && quality > MIN_Q) {
      quality = Math.max(MIN_Q, quality - STEP);
      blob = await canvasToBlob(canvas, 'image/jpeg', quality);
    }
    if (blob.size <= MAX_BYTES) break;

    // まだ大きい → 解像度も落として再挑戦
    w = Math.max(1, Math.round(w * 0.75));
    h = Math.max(1, Math.round(h * 0.75));
    quality = 0.85;
  }

  const name = (file.name.replace(/\.[^.]+$/, '') || 'image') + '.jpg';
  return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() });
}

function loadImageFromFile(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
    img.onerror = (e) => { URL.revokeObjectURL(url); reject(e); };
    img.src = url;
  });
}

function canvasToBlob(canvas, type, quality) {
  return new Promise((resolve) => canvas.toBlob(resolve, type, quality));
}

// 画像プレビュー表示

document.getElementById('file-input').addEventListener('change', function (event) {
    const preview = document.getElementById('preview');
    preview.innerHTML = ""; // 前のプレビューを消す

    const file = event.target.files[0];
    if (file && file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = function (e) {
        const img = document.createElement("img");
        img.src = e.target.result;
        preview.appendChild(img);
      };
      reader.readAsDataURL(file);
    }
  });
