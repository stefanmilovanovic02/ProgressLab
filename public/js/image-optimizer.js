(function () {
  async function decode(file) {
    if ('createImageBitmap' in window) {
      return createImageBitmap(file, { imageOrientation: 'from-image' });
    }

    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(file);
      const image = new Image();
      image.onload = () => {
        URL.revokeObjectURL(url);
        resolve(image);
      };
      image.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error('This image format could not be optimized by your browser.'));
      };
      image.src = url;
    });
  }

  function encode(canvas, type, quality) {
    return new Promise(resolve => canvas.toBlob(resolve, type, quality));
  }

  async function optimize(file, options = {}) {
    const maxDimension = options.maxDimension || 1600;
    const targetBytes = options.targetBytes || 900 * 1024;
    const quality = options.quality || .84;
    const baseName = options.baseName || 'image';
    const source = await decode(file);
    const sourceWidth = source.width || source.naturalWidth;
    const sourceHeight = source.height || source.naturalHeight;
    let scale = Math.min(1, maxDimension / Math.max(sourceWidth, sourceHeight));
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { alpha: false });

    async function render(renderScale, renderQuality) {
      canvas.width = Math.max(1, Math.round(sourceWidth * renderScale));
      canvas.height = Math.max(1, Math.round(sourceHeight * renderScale));
      context.fillStyle = '#ffffff';
      context.fillRect(0, 0, canvas.width, canvas.height);
      context.drawImage(source, 0, 0, canvas.width, canvas.height);

      let blob = await encode(canvas, 'image/webp', renderQuality);
      let extension = 'webp';
      if (!blob || blob.type !== 'image/webp') {
        blob = await encode(canvas, 'image/jpeg', renderQuality);
        extension = 'jpg';
      }

      return { blob, extension };
    }

    let result = await render(scale, quality);
    if (!result.blob) {
      if (typeof source.close === 'function') source.close();
      throw new Error('This image could not be optimized. Please choose another image.');
    }

    if (result.blob.size > targetBytes && scale > .45) {
      const reduction = Math.max(.7, Math.sqrt(targetBytes / result.blob.size) * .96);
      scale *= reduction;
      result = await render(scale, Math.max(.76, quality - .05));
    }

    if (typeof source.close === 'function') source.close();

    if (result.blob.size >= file.size && file.size <= targetBytes) {
      return {
        file,
        originalBytes: file.size,
        optimizedBytes: file.size,
        changed: false,
      };
    }

    return {
      file: new File([result.blob], `${baseName}.${result.extension}`, {
        type: result.blob.type,
        lastModified: Date.now(),
      }),
      originalBytes: file.size,
      optimizedBytes: result.blob.size,
      changed: true,
    };
  }

  window.ProgressLabImageOptimizer = { optimize };
})();
