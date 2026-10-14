// assets/js/signature.js
// MODULE ES NAVIGATEUR — Gestionnaire natif de signature électronique sur canvas HTML5.
// Compatible souris, stylet et tactile (PointerEvents + TouchEvents).
// Respecte la charte graphique Imolode (teintes sombres #1C1C1C, aucun framework externe).

export class SignaturePad {
  /**
   * @param {HTMLCanvasElement} canvas
   * @param {Object} [options]
   * @param {string} [options.color]
   * @param {number} [options.lineWidth]
   * @param {function(boolean):void} [options.onChange]
   */
  constructor(canvas, options = {}) {
    if (!canvas || !(canvas instanceof HTMLCanvasElement)) {
      throw new Error('SignaturePad: un élément <canvas> valide est requis.');
    }

    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');
    this.color = options.color || '#1C1C1C';
    this.lineWidth = options.lineWidth || 2.2;
    this.onChange = options.onChange || null;

    this.isDrawing = false;
    this.hasSignature = false;
    this.strokes = []; // Tableau de tracés pour réaffichage fidèle au redimensionnement
    this.currentStroke = null;
    this._lastPoint = null;
    this._dpr = window.devicePixelRatio || 1;

    // Empêcher le défilement lors du dessin sur écran tactile
    this.canvas.style.touchAction = 'none';

    this._boundOnPointerDown = this._handlePointerDown.bind(this);
    this._boundOnPointerMove = this._handlePointerMove.bind(this);
    this._boundOnPointerUp = this._handlePointerUp.bind(this);
    this._boundOnTouchStart = this._handleTouchStart.bind(this);
    this._boundOnTouchMove = this._handleTouchMove.bind(this);
    this._boundOnTouchEnd = this._handleTouchEnd.bind(this);
    this._boundResize = this._handleResize.bind(this);

    this._initCanvas();
    this._bindEvents();
  }

  _initCanvas() {
    const rect = this.canvas.getBoundingClientRect();
    const width = rect.width || 400;
    const height = rect.height || 180;
    this._dpr = window.devicePixelRatio || 1;

    this.canvas.width = Math.round(width * this._dpr);
    this.canvas.height = Math.round(height * this._dpr);

    this.ctx.scale(this._dpr, this._dpr);
    this.ctx.strokeStyle = this.color;
    this.ctx.fillStyle = this.color;
    this.ctx.lineWidth = this.lineWidth;
    this.ctx.lineCap = 'round';
    this.ctx.lineJoin = 'round';
  }

  _bindEvents() {
    // Événements Pointer (modernes, standard pour souris / stylet / tactile)
    if (window.PointerEvent) {
      this.canvas.addEventListener('pointerdown', this._boundOnPointerDown, { passive: false });
      window.addEventListener('pointermove', this._boundOnPointerMove, { passive: false });
      window.addEventListener('pointerup', this._boundOnPointerUp);
      window.addEventListener('pointercancel', this._boundOnPointerUp);
    } else {
      // Événements Touch de repli pour navigateurs plus anciens
      this.canvas.addEventListener('touchstart', this._boundOnTouchStart, { passive: false });
      window.addEventListener('touchmove', this._boundOnTouchMove, { passive: false });
      window.addEventListener('touchend', this._boundOnTouchEnd);
      window.addEventListener('touchcancel', this._boundOnTouchEnd);

      // Événements Souris classiques
      this.canvas.addEventListener('mousedown', this._boundOnPointerDown);
      window.addEventListener('mousemove', this._boundOnPointerMove);
      window.addEventListener('mouseup', this._boundOnPointerUp);
    }

    // Gestion du redimensionnement (rotation mobile, resize écran)
    window.addEventListener('resize', this._boundResize);
  }

  _getCoordinates(event) {
    const rect = this.canvas.getBoundingClientRect();
    let clientX, clientY;

    if (event.touches && event.touches.length > 0) {
      clientX = event.touches[0].clientX;
      clientY = event.touches[0].clientY;
    } else if (event.changedTouches && event.changedTouches.length > 0) {
      clientX = event.changedTouches[0].clientX;
      clientY = event.changedTouches[0].clientY;
    } else {
      clientX = event.clientX;
      clientY = event.clientY;
    }

    return {
      x: clientX - rect.left,
      y: clientY - rect.top
    };
  }

  _startStroke(coords) {
    this.isDrawing = true;
    this.hasSignature = true;
    this._lastPoint = coords;
    this.currentStroke = [coords];

    // Tracer un point immédiat (permet de dessiner même par simple clic/tap)
    this.ctx.beginPath();
    this.ctx.arc(coords.x, coords.y, this.lineWidth / 2, 0, Math.PI * 2);
    this.ctx.fill();

    if (typeof this.onChange === 'function') {
      this.onChange(true);
    }
  }

  _moveStroke(coords) {
    if (!this.isDrawing || !this._lastPoint) return;

    this.ctx.beginPath();
    this.ctx.moveTo(this._lastPoint.x, this._lastPoint.y);
    this.ctx.lineTo(coords.x, coords.y);
    this.ctx.stroke();

    this._lastPoint = coords;
    if (this.currentStroke) {
      this.currentStroke.push(coords);
    }
  }

  _endStroke() {
    if (!this.isDrawing) return;
    this.isDrawing = false;
    this._lastPoint = null;
    if (this.currentStroke && this.currentStroke.length > 0) {
      this.strokes.push(this.currentStroke);
      this.currentStroke = null;
    }
  }

  _handlePointerDown(e) {
    if (e.button !== undefined && e.button !== 0) return; // Seul le clic gauche / contact principal
    e.preventDefault();
    const coords = this._getCoordinates(e);
    this._startStroke(coords);
  }

  _handlePointerMove(e) {
    if (!this.isDrawing) return;
    e.preventDefault();
    const coords = this._getCoordinates(e);
    this._moveStroke(coords);
  }

  _handlePointerUp() {
    this._endStroke();
  }

  _handleTouchStart(e) {
    e.preventDefault();
    const coords = this._getCoordinates(e);
    this._startStroke(coords);
  }

  _handleTouchMove(e) {
    if (!this.isDrawing) return;
    e.preventDefault();
    const coords = this._getCoordinates(e);
    this._moveStroke(coords);
  }

  _handleTouchEnd() {
    this._endStroke();
  }

  _handleResize() {
    if (!this.canvas) return;
    const oldWidth = this.canvas.width / this._dpr;
    const oldHeight = this.canvas.height / this._dpr;

    this._initCanvas();

    const newWidth = this.canvas.width / this._dpr;
    const newHeight = this.canvas.height / this._dpr;

    if (oldWidth > 0 && oldHeight > 0 && this.strokes.length > 0) {
      const scaleX = newWidth / oldWidth;
      const scaleY = newHeight / oldHeight;

      // Rejouer les traits
      this.strokes.forEach((stroke) => {
        if (!stroke.length) return;
        this.ctx.beginPath();
        const first = stroke[0];
        this.ctx.arc(first.x * scaleX, first.y * scaleY, this.lineWidth / 2, 0, Math.PI * 2);
        this.ctx.fill();

        if (stroke.length > 1) {
          this.ctx.beginPath();
          this.ctx.moveTo(first.x * scaleX, first.y * scaleY);
          for (let i = 1; i < stroke.length; i++) {
            this.ctx.lineTo(stroke[i].x * scaleX, stroke[i].y * scaleY);
          }
          this.ctx.stroke();
        }
      });
    }
  }

  /**
   * Efface le canvas et réinitialise l'état.
   */
  clear() {
    const width = this.canvas.width / this._dpr;
    const height = this.canvas.height / this._dpr;
    this.ctx.clearRect(0, 0, width, height);
    this.strokes = [];
    this.currentStroke = null;
    this._lastPoint = null;
    this.hasSignature = false;
    this.isDrawing = false;

    if (typeof this.onChange === 'function') {
      this.onChange(false);
    }
  }

  /**
   * Indique si la zone de signature est vide.
   * @returns {boolean}
   */
  isEmpty() {
    return !this.hasSignature || this.strokes.length === 0;
  }

  /**
   * Exporte la signature sous forme d'image DataURL (PNG).
   * @returns {string|null}
   */
  toDataURL(type = 'image/png') {
    if (this.isEmpty()) return null;
    return this.canvas.toDataURL(type);
  }

  /**
   * Nettoie les écouteurs d'événements.
   */
  destroy() {
    if (window.PointerEvent) {
      this.canvas.removeEventListener('pointerdown', this._boundOnPointerDown);
      window.removeEventListener('pointermove', this._boundOnPointerMove);
      window.removeEventListener('pointerup', this._boundOnPointerUp);
      window.removeEventListener('pointercancel', this._boundOnPointerUp);
    } else {
      this.canvas.removeEventListener('touchstart', this._boundOnTouchStart);
      window.removeEventListener('touchmove', this._boundOnTouchMove);
      window.removeEventListener('touchend', this._boundOnTouchEnd);
      window.removeEventListener('touchcancel', this._boundOnTouchEnd);

      this.canvas.removeEventListener('mousedown', this._boundOnPointerDown);
      window.removeEventListener('mousemove', this._boundOnPointerMove);
      window.removeEventListener('mouseup', this._boundOnPointerUp);
    }
    window.removeEventListener('resize', this._boundResize);
  }
}

/**
 * Fonction d'initialisation rapide d'un canvas de signature.
 * @param {HTMLCanvasElement|string} target
 * @param {Object} [options]
 * @returns {SignaturePad}
 */
export function initSignature(target, options = {}) {
  const canvas = typeof target === 'string' ? document.querySelector(target) : target;
  return new SignaturePad(canvas, options);
}
