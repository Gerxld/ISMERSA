<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/partials/header.php';
?>

<!-- CTA STRIP -->
<div class="bg-light rounded-3 shadow-sm p-4 mb-3 text-center">
  <p class="mb-3 fs-5">
    <strong>¿Buscas repuestos de transmisión?</strong><br>
    Explora nuestro catálogo publicado.
  </p>
  <a href="<?= public_url('productos.php') ?>" class="btn btn-outline-dark btn-lg fw-semibold">
    Ver catálogo
  </a>
</div>

<!-- HERO / CAROUSEL -->
<div id="heroCarousel" class="carousel slide mb-4" data-bs-ride="carousel" data-bs-interval="3000" data-bs-touch="true">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
  </div>
  <div class="carousel-inner rounded-3 shadow-sm">
    <div class="carousel-item active">
      <img src="../uploads/imagenes/Ismersa-banner.jpg" class="d-block w-100" alt="Repuestos de transmisión - banner 1" style="max-height:520px;object-fit:cover;">
    </div>
    <div class="carousel-item">
      <img src="../uploads/imagenes/Ismersa-producto2.jpg" class="d-block w-100" alt="Repuestos de transmisión - banner 2" style="max-height:520px;object-fit:cover;">
      <div class="carousel-caption d-none d-md-block">
        <h5 class="fw-bold">Asesoría técnica</h5>
        <p>Te ayudamos a elegir el componente correcto.</p>
      </div>
    </div>
    <div class="carousel-item">
      <img src="../uploads/imagenes/Ismersa-producto1.jpg" class="d-block w-100" alt="Repuestos de transmisión - banner 3" style="max-height:520px;object-fit:cover;">
      <div class="carousel-caption d-none d-md-block text-end">
        <h5 class="fw-bold">Entrega rápida</h5>
        <p>Calidad y disponibilidad para tu taller o proyecto.</p>
      </div>
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span>
  </button>
</div>

<!-- CONÓCENOS -->
<section class="py-4">
  <div class="row align-items-center g-4">
    <div class="col-lg-6">
      <h2 class="fw-bold mb-3">Conócenos</h2>
      <p class="lead">En <strong>ISMERSA</strong> somos especialistas en repuestos para transmisiones de vehículos automotores. Trabajamos con marcas confiables y probadas en el mercado, garantizando calidad y respaldo técnico.</p>
      <p>Apoyamos a talleres y entusiastas con asesoría, disponibilidad de inventario y procesos ágiles de compra.</p>
    </div>
    <div class="col-lg-6">
      <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm">
        <img src="../uploads/imagenes/Ismersa-local.jpg" class="w-100 h-100" alt="Equipo ISMERSA" style="object-fit:cover;">
      </div>
    </div>
  </div>
</section>

<hr class="my-5">

<!-- QUIÉNES SOMOS -->
<section class="py-2">
  <div class="row g-4">
    <div class="col-lg-6 order-lg-2">
      <h2 class="fw-bold mb-3">Quiénes somos</h2>
      <p>Somos un equipo comprometido con la confiabilidad y la atención al cliente. Nuestro catálogo incluye filtros, kits de empaques, solenoides, sensores, bombas y más, con una selección pensada para resolver necesidades reales de diagnóstico y reparación.</p>
      <ul class="mb-0">
        <li>Selección de repuestos con especificaciones claras.</li>
        <li>Soporte previo y posterior a la compra.</li>
        <li>Gestión de stock y reposición continua.</li>
      </ul>
    </div>
    <div class="col-lg-6 order-lg-1">
      <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm">
        <img src="../uploads/imagenes/Ismersa-local.jpg" class="w-100 h-100" alt="Quiénes somos" style="object-fit:cover;">
      </div>
    </div>
  </div>
</section>

<hr class="my-5">

<!-- DÓNDE ESTAMOS -->
<section class="py-2">
  <div class="row g-4 align-items-center">
    <div class="col-lg-5">
      <h2 class="fw-bold mb-3">Dónde estamos</h2>
      <p>Visítanos en nuestra tienda física. Haz clic en el mapa para abrir la ubicación en Google Maps.</p>
      <a class="btn btn-outline-primary" target="_blank"
         href="https://maps.app.goo.gl/B31s9xYfGcf8e2v47">
        Abrir en Google Maps
      </a>
    </div>
    <div class="col-lg-7">
      <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm">
        <iframe
          src="https://www.google.com/maps?q=Ismersa%20Transmisiones%20Panama&output=embed"
          allowfullscreen
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </div>
  </div>
</section>

<hr class="my-5">

<!-- CONTÁCTANOS -->
<section class="py-2">
  <div class="row g-4">
    <div class="col-lg-6">
      <h2 class="fw-bold mb-3">Contáctanos</h2>
      <p class="mb-1">Correo: <a href="mailto:Ismersa507@gmail.com">Ismersa507@gmail.com</a></p>
      <p class="mb-1">Teléfono: <a href="tel:3955456">395-5456</a></p>
      <p class="mb-1">Celular: <a href="tel:67244138">6724-4138</a></p>
      <p class="text-muted small">Horario: Lun–Vie 8:00–17:00</p>
      <p class="text-muted small">Horario: Sábados 8:00–12:00</p>
      <p class="text-muted small">Horario: Domingos Cerrados</p>
    </div>
    <div class="col-lg-6">
      <div class="p-4 bg-white rounded-3 shadow-sm">
        <form onsubmit="return false;">
          <div class="mb-3">
            <label class="form-label">Tu nombre</label>
            <input type="text" class="form-control" placeholder="Escribe tu nombre">
          </div>
          <div class="mb-3">
            <label class="form-label">Tu correo</label>
            <input type="email" class="form-control" placeholder="tucorreo@correo.com">
          </div>
          <div class="mb-3">
            <label class="form-label">Mensaje</label>
            <textarea class="form-control" rows="3" placeholder="¿Cómo podemos ayudarte?"></textarea>
          </div>
          <button class="btn btn-primary w-100" disabled>Enviar (próximamente)</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
