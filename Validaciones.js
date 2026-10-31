// ========== VALIDACIONES.JS ==========
// Reglas de validación reutilizables para todos los formularios

const V = {

    // Muestra error en campo
    error: function(id, msg) {
        const el = document.getElementById(id);
        el.style.borderColor = '#f87171';
        el.style.boxShadow = '0 0 8px rgba(248,113,113,0.4)';
        let hint = document.getElementById('hint-' + id);
        if (!hint) {
            hint = document.createElement('span');
            hint.id = 'hint-' + id;
            hint.style.cssText = 'color:#f87171;font-size:0.78rem;margin-top:-8px;display:block;';
            el.parentNode.insertBefore(hint, el.nextSibling);
        }
        hint.textContent = '⚠ ' + msg;
        return false;
    },

    // Limpia error de campo
    ok: function(id) {
        const el = document.getElementById(id);
        el.style.borderColor = '#34d399';
        el.style.boxShadow = '0 0 8px rgba(52,211,153,0.3)';
        const hint = document.getElementById('hint-' + id);
        if (hint) hint.textContent = '';
        return true;
    },

    // Limpia todos los estilos del campo
    limpiar: function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.style.borderColor = '';
        el.style.boxShadow = '';
        const hint = document.getElementById('hint-' + id);
        if (hint) hint.textContent = '';
    },

    // Bloquea caracteres en tiempo real según regex
    bloquear: function(idCampo, regexPermitidos) {
        document.getElementById(idCampo).addEventListener('keypress', function(e) {
            if (!regexPermitidos.test(e.key)) e.preventDefault();
        });
        // También bloquea pegar texto inválido
        document.getElementById(idCampo).addEventListener('paste', function(e) {
            e.preventDefault();
            const texto = (e.clipboardData || window.clipboardData).getData('text');
            const limpio = texto.split('').filter(c => regexPermitidos.test(c)).join('');
            document.execCommand('insertText', false, limpio);
        });
    },

    // ---- REGLAS ----

    usuario: function(id) {
    const val = document.getElementById(id).value.trim();
    if (!val) return this.error(id, 'El usuario es obligatorio.');
    if (val.length < 3 || val.length > 20) return this.error(id, 'Entre 3 y 20 caracteres.');
    if (!/[a-zA-Z]/.test(val)) return this.error(id, 'El usuario debe contener al menos una letra.');
    if (!/^[a-zA-Z0-9_]+$/.test(val)) return this.error(id, 'Solo letras, números y guión bajo. Sin espacios.');
    return this.ok(id);
},
    contrasena: function(id) {
        const val = document.getElementById(id).value;
        if (!val) return this.error(id, 'La contraseña es obligatoria.');
        if (val.length < 6) return this.error(id, 'Mínimo 6 caracteres.');
        if (val.length > 30) return this.error(id, 'Máximo 30 caracteres.');
        return this.ok(id);
    },

    nombre: function(id) {
        const val = document.getElementById(id).value.trim();
        if (!val) return this.error(id, 'El nombre es obligatorio.');
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,60}$/.test(val)) return this.error(id, 'Solo letras y espacios. Sin números ni símbolos.');
        return this.ok(id);
    },

    telefono: function(id) {
        const val = document.getElementById(id).value.trim();
        if (!val) return this.error(id, 'El teléfono es obligatorio.');
        if (!/^\d{7,10}$/.test(val)) return this.error(id, 'Solo dígitos. Entre 7 y 10 números.');
        return this.ok(id);
    },

    correo: function(id) {
        const val = document.getElementById(id).value.trim();
        if (!val) return this.ok(id); // correo es opcional
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) return this.error(id, 'Formato inválido. Ej: nombre@correo.com');
        return this.ok(id);
    },

    correoRequerido: function(id) {
        const val = document.getElementById(id).value.trim();
        if (!val) return this.error(id, 'El correo es obligatorio.');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) return this.error(id, 'Formato inválido. Ej: nombre@correo.com');
        return this.ok(id);
    },

    producto: function(id) {
        const val = document.getElementById(id).value.trim();
        if (!val) return this.error(id, 'El producto es obligatorio.');
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-]{2,60}$/.test(val)) return this.error(id, 'Solo letras, números, espacios y guión.');
        return this.ok(id);
    },

    cantidad: function(id) {
        const val = document.getElementById(id).value;
        if (!val) return this.error(id, 'La cantidad es obligatoria.');
        if (!/^\d+$/.test(val)) return this.error(id, 'Solo números enteros positivos.');
        if (parseInt(val) < 1) return this.error(id, 'La cantidad mínima es 1.');
        if (parseInt(val) > 999999) return this.error(id, 'Cantidad demasiado grande.');
        return this.ok(id);
    },

    precio: function(id) {
        const val = document.getElementById(id).value;
        if (!val) return this.error(id, 'El precio es obligatorio.');
        if (!/^\d+(\.\d{1,2})?$/.test(val)) return this.error(id, 'Solo números positivos. Máx 2 decimales.');
        if (parseFloat(val) <= 0) return this.error(id, 'El precio debe ser mayor a 0.');
        return this.ok(id);
    },

    fecha: function(id, permitirFutura = true) {
        const val = document.getElementById(id).value;
        if (!val) return this.error(id, 'La fecha es obligatoria.');
        const hoy = new Date().toISOString().split('T')[0];
        if (!permitirFutura && val > hoy) return this.error(id, 'La fecha no puede ser futura.');
        return this.ok(id);
    },

    select: function(id, nombre) {
        const val = document.getElementById(id).value;
        if (!val || val === '') return this.error(id, 'Selecciona ' + (nombre || 'una opción') + '.');
        return this.ok(id);
    }
};