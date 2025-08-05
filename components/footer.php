<footer class="footer mt-5 pt-4 pb-2 border-top shadow-lg" style="background: linear-gradient(90deg, #90caf9 0%, #ffe0b2 100%); color: #222; border-color: #eee !important; font-family: 'Montserrat', Arial, sans-serif; box-shadow: 0 -4px 24px 0 rgba(0,0,0,0.07);">
    <div class="container">
        <!-- Banners destacados en el pie -->
        <div class="row justify-content-center mb-2">
            <div class="col-lg-10">
                <div class="d-flex flex-column flex-md-row gap-3 justify-content-center align-items-stretch">
                    <!-- Instagram -->
                    <div class="footer-banner d-flex align-items-center gap-2 flex-grow-1" style="background: linear-gradient(90deg, #fdf6ee 0%, #e3f2fd 100%); border: 1.5px solid #fd7e14; border-radius: 1.5em; padding: 0.7em 1.2em; min-width: 0;">
                        <span style="font-size: 1.7rem; background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: flex; align-items: center;"><i class="bi bi-instagram"></i></span>
                        <span style="font-size: 1.02rem; color: #222;">
                            Seguinos en <b>Instagram</b> para novedades, sorteos y tips:<br>
                            <a href="https://www.instagram.com/rinconfreya/" target="_blank" rel="noopener" style="color: #dc2743; font-weight: 600; text-decoration: underline;">@rinconfreya</a>
                        </span>
                    </div>
                    <!-- WhatsApp -->
                    <div class="footer-banner d-flex align-items-center gap-2 flex-grow-1" style="background: linear-gradient(90deg, #e3f2fd 0%, #e0f7fa 100%); border: 1.5px solid #25d366; border-radius: 1.5em; padding: 0.7em 1.2em; min-width: 0;">
                        <span style="font-size: 1.7rem; color: #25d366; display: flex; align-items: center;"><i class="bi bi-whatsapp"></i></span>
                        <span style="font-size: 1.02rem; color: #222;">
                            Escribinos por cualquier consulta de nuestros productos o para consultar sobre nuestras terapias y sanaciones holísticas:<br>
                            <a href="https://wa.me/5491161965488" target="_blank" rel="noopener" style="color: #128c7e; font-weight: 600; text-decoration: underline;">+54 9 11 6196-5488</a>
                        </span>
                    </div>
                    <!-- Email -->
                    <div class="footer-banner d-flex align-items-center gap-2 flex-grow-1" style="background: linear-gradient(90deg, #ffe0b2 0%, #fdf6ee 100%); border: 1.5px solid #fd7e14; border-radius: 1.5em; padding: 0.7em 1.2em; min-width: 0;">
                        <span style="font-size: 1.7rem; color: #fd7e14; display: flex; align-items: center;"><i class="bi bi-envelope"></i></span>
                        <span style="font-size: 1.02rem; color: #222;">
                            ¿Tenés dudas o querés coordinar una consulta personalizada?<br>
                            Escribinos a <a href="mailto:consultas@rincondefreya.com.ar" style="color: #fd7e14; font-weight: 600; text-decoration: underline;">consultas <br>@rincondefreya.com.ar</a> y te responderemos a la brevedad.
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fin banners destacados en el pie -->
        <div class="row">
            <div class="col text-center">
                <small class="footer-copyright">&copy; <?php echo date('Y'); ?> Rincón de Freya. Todos los derechos reservados.</small>
            </div>
        </div>
    </div>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .footer, .footer * {
            font-family: 'Montserrat', Arial, sans-serif !important;
        }
        .footer {
            background: linear-gradient(90deg, #90caf9 0%, #ffe0b2 100%) !important;
            color: #222;
            border-top: 1px solid #eee !important;
            box-shadow: 0 -4px 24px 0 rgba(0,0,0,0.07);
        }
        .footer-social {
            color: #222;
            font-size: 1.08rem;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .footer-social .footer-icon-bg {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            font-size: 1.5rem;
            background: #fff;
            border: 1.5px solid #fd7e14;
            margin-bottom: 2px;
            color: #fd7e14;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .footer-social.footer-ig .footer-icon-bg { color: #dc2743; border-color: #dc2743; }
        .footer-social.footer-wa .footer-icon-bg { color: #128c7e; border-color: #128c7e; }
        .footer-social.footer-mail .footer-icon-bg { color: #fd7e14; border-color: #fd7e14; }
        .footer-social:hover {
            color: #fd7e14;
        }
        .footer-social:hover .footer-icon-bg {
            background: #fff7ef;
            box-shadow: 0 2px 8px rgba(253,126,20,0.13);
        }
        .footer-social-text {
            font-size: 1.08rem;
            font-weight: 500;
            letter-spacing: 0.1px;
        }
        .footer-copyright {
            color: #444;
            font-size: 1.02rem;
            font-weight: 400;
            letter-spacing: 0.2px;
        }
        @media (max-width: 767px) {
            .footer-social-text {
                display: none;
            }
            .footer-icon-bg {
                margin-right: 0;
            }
        }
    </style>
</footer>