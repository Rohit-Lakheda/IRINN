<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>IRINN Self Help Portal</title>
  <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
  <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ time() }}">

  <!-- Local Bootstrap CSS -->
  <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">

  <!-- Custom Theme CSS -->
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

  <style>
      :root {
          --irinn-deep-blue: #0b1f3b;
          --irinn-mid-blue: #1f4f7b;
          --irinn-cyan: #3ec4ff;
          --irinn-soft-bg: #f5f7fb;
          --irinn-accent: #ffd36b;
      }

      html, body {
          height: 100%;
      }

      body {
          margin: 0;
          font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
          background: radial-gradient(circle at top left, rgba(62,196,255,0.25), transparent 55%),
                      radial-gradient(circle at bottom right, rgba(255,211,107,0.2), transparent 55%),
                      var(--irinn-soft-bg);
          color: #1a1a1a;
      }

      .irin-hero-wrapper {
          min-height: calc(100vh - 60px);
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 2rem 1.5rem 3rem;
      }

      .irin-hero-card {
          position: relative;
          width: 100%;
          max-width: 1100px;
          border-radius: 24px;
          overflow: hidden;
          background: linear-gradient(135deg, rgba(11,31,59,0.94), rgba(31,79,123,0.96));
          box-shadow: 0 22px 40px rgba(0, 0, 0, 0.18);
          display: grid;
          grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
      }

      .irin-visual-pane {
          position: relative;
          padding: 2.5rem 2.25rem;
          color: #f8fbff;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          background:
              radial-gradient(circle at top left, rgba(62,196,255,0.35), transparent 55%),
              radial-gradient(circle at bottom, rgba(255,211,107,0.18), transparent 55%);
      }

      .irin-image-frame {
          position: relative;
          border-radius: 18px;
          overflow: hidden;
          border: 1px solid rgba(255, 255, 255, 0.14);
          background: linear-gradient(135deg, rgba(10, 26, 52, 0.85), rgba(13, 47, 88, 0.92));
      }

      .irin-image-frame::before {
          content: "";
          position: absolute;
          inset: 0;
          background-image: url("{{ asset('images/IRINN.png') }}");
          background-size: cover;
          background-position: center;
          opacity: 0.85;
          filter: saturate(1.05);
      }

      .irin-image-overlay {
          position: relative;
          padding: 1.75rem 1.5rem;
          display: flex;
          flex-direction: column;
          justify-content: flex-end;
          min-height: 260px;
          background: linear-gradient(to top, rgba(7, 17, 35, 0.96), rgba(7, 17, 35, 0.3));
      }

      .irin-image-chip {
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
          padding: 0.3rem 0.9rem;
          border-radius: 999px;
          background: rgba(11,31,59,0.78);
          border: 1px solid rgba(255, 255, 255, 0.15);
          font-size: 0.78rem;
          letter-spacing: .04em;
          text-transform: uppercase;
      }

      .irin-image-chip-dot {
          width: 7px;
          height: 7px;
          border-radius: 999px;
          background: var(--irinn-cyan);
          box-shadow: 0 0 6px rgba(62,196,255,0.9);
      }

      .irin-image-title {
          margin-top: 1.25rem;
          font-size: 1.3rem;
          font-weight: 600;
      }

      .irin-image-subtitle {
          margin-top: 0.35rem;
          font-size: 0.9rem;
          color: rgba(235, 244, 255, 0.9);
      }

      .irin-hero-content {
          padding: 2.5rem 2.4rem;
          background: radial-gradient(circle at top right, rgba(62,196,255,0.22), transparent 58%),
                      rgba(4,11,24,0.96);
          color: #fdfdfd;
          border-left: 1px solid rgba(255, 255, 255, 0.08);
      }

      .irin-kicker {
          display: inline-flex;
          align-items: center;
          gap: .45rem;
          font-size: 0.78rem;
          text-transform: uppercase;
          letter-spacing: .16em;
          font-weight: 600;
          color: rgba(209, 230, 255, 0.94);
      }

      .irin-kicker-pill {
          width: 26px;
          height: 2px;
          border-radius: 999px;
          background: linear-gradient(90deg, var(--irinn-cyan), var(--irinn-accent));
      }

      .irin-title {
          margin-top: 0.9rem;
          font-size: 1.9rem;
          font-weight: 650;
          letter-spacing: 0.02em;
          color: #ffffff !important;
          text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
      }

      .irin-subtitle {
          margin-top: 0.55rem;
          font-size: 0.94rem;
          color: rgba(225,234,248,0.9);
          max-width: 30rem;
      }

      .irin-cta-group {
          margin-top: 1.7rem;
          display: flex;
          flex-wrap: wrap;
          gap: 0.75rem;
      }

      .irin-btn-primary,
      .irin-btn-secondary {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          gap: .5rem;
          padding: 0.7rem 1.6rem;
          border-radius: 999px;
          border: 1px solid transparent;
          font-size: 0.96rem;
          font-weight: 600;
          text-decoration: none;
          transition: all 0.2s ease-out;
          cursor: pointer;
          min-width: 150px;
      }

      .irin-btn-primary {
          background: linear-gradient(120deg, var(--irinn-cyan), var(--irinn-accent));
          color: #0b1324 !important;
          box-shadow: 0 10px 22px rgba(0,0,0,0.35);
      }

      .irin-btn-primary:hover {
          transform: translateY(-1px);
          box-shadow: 0 14px 30px rgba(0, 0, 0, 0.42);
          color: #0b1324 !important;
          background: linear-gradient(120deg, var(--irinn-cyan), var(--irinn-accent));
      }

      .irin-btn-primary:hover * {
          color: #0b1324 !important;
      }

      .irin-btn-secondary {
          background: transparent;
          color: #e5edff;
          border-color: rgba(220, 231, 251, 0.55);
      }

      .irin-btn-secondary:hover {
          background: rgba(13, 47, 88, 0.85);
          border-color: rgba(255, 255, 255, 0.8);
      }

      .irin-meta {
          margin-top: 1.7rem;
          font-size: 0.8rem;
          color: rgba(202, 214, 238, 0.9);
      }

      .irin-meta strong {
          color: var(--irinn-cyan);
          font-weight: 600;
      }

      .irin-footer-note {
          margin-top: 0.35rem;
          font-size: 0.78rem;
          color: rgba(183, 197, 222, 0.9);
      }

      @media (max-width: 991.98px) {
          .irin-hero-card {
              grid-template-columns: minmax(0, 1fr);
          }

          .irin-visual-pane {
              padding: 2.1rem 2rem 1.6rem;
          }

          .irin-hero-content {
              padding: 1.8rem 1.8rem 2.1rem;
          }
      }

      @media (max-width: 767.98px) {
          .irin-hero-wrapper {
              padding: 1.5rem 1rem 2.5rem;
          }

          .irin-hero-card {
              border-radius: 18px;
          }

          .irin-visual-pane {
              padding: 1.5rem 1.3rem 1.3rem;
          }

          .irin-image-overlay {
              padding: 1.3rem 1.15rem;
              min-height: 210px;
          }

          .irin-title {
              font-size: 1.6rem;
          }

          .irin-hero-content {
              padding: 1.5rem 1.35rem 1.8rem;
          }

          .irin-logo-inline {
              top: 0.8rem;
              right: 0.8rem;
              padding: 0.4rem 0.6rem;
          }

          .irin-logo-inline .nixi-logo {
              height: 30px;
              max-width: 85px;
          }
      }
      .irin-logo-inline {
          position: absolute;
          top: 1.2rem;
          right: 1.4rem;
          z-index: 3;
          background: #ffffff;
          padding: 0.5rem 0.75rem;
          border-radius: 5px;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      }

      .irin-logo-inline .nixi-logo-link {
          display: inline-block;
      }

      .irin-logo-inline .nixi-logo {
          height: 35px;
          max-width: 100px;
          width: auto;
      }
  </style>
</head>
<body>
  <main class="irin-hero-wrapper">
      <section class="irin-hero-card position-relative">
          <div class="irin-logo-inline">
              @include('partials.logo')
          </div>
          <div class="irin-visual-pane">
              <div class="irin-image-frame">
                  <div class="irin-image-overlay">
                      <div class="irin-image-chip">
                          <span class="irin-image-chip-dot"></span>
                          <span>IRINN • NIXI</span>
                      </div>
                      <div class="irin-image-title">
                          Internet Resource Management<br/>
                          for a connected India
                      </div>
                      <div class="irin-image-subtitle">
                          Secure, guided workflows to help organisations and individuals
                          manage IRINN applications with confidence.
                      </div>
                  </div>
              </div>
          </div>
          <div class="irin-hero-content">
              <div class="irin-kicker">
                  <span class="irin-kicker-pill"></span>
                  <span>IRINN Self Help Portal</span>
              </div>
              <h1 class="irin-title">Welcome to the IRINN Self Help Portal</h1>
              <p class="irin-subtitle">
                  Sign in to continue an existing application or register as a new user
                  to start your IRINN journey. Clear steps, formal guidance, and a
                  simple experience designed for operators and organisations.
              </p>
              <div class="irin-cta-group">
                  <a href="{{ route('login.index') }}" class="irin-btn-primary">
                      Log in
                  </a>
                  <a href="{{ route('register.index') }}" class="irin-btn-secondary">
                      Register new user
                  </a>
              </div>
              <div class="irin-meta">
                  Managed by <strong>National Internet Exchange of India (NIXI)</strong>
                  <div class="irin-footer-note">
                      Use the same account across IRINN services. You can always return
                      to this page from any screen using the NIXI logo.
                  </div>
              </div>
          </div>
      </section>
  </main>
</body>
</html>
