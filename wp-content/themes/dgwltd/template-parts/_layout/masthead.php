<header id="masthead" class="dgwltd-masthead" enabled="false">

    <div id="skiplink-container">
        <a href="#content" class="govuk-skip-link" data-module="govuk-skip-link"><?php esc_html_e( 'Skip to main content', 'dgwltd' ); ?></a>
    </div>

    <div class="dgwltd-masthead-container">

            <div class="dgwltd-masthead__logo">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" title="Go to the homepage for <?php bloginfo( 'name' ); ?>">
                <?php get_template_part( 'template-parts/_atoms/logo' ); ?>
                <span class="visually-hidden"><?php esc_html_e( 'DGW.ltd', 'dgwltd' ); ?></span>
                </a>
            </div><!-- .masthead__logo -->

            <nav id="site-navigation" class="main-navigation dgwltd-nav" aria-label="primary">

                <button class="nav-toggle" id="nav-toggle" aria-label="Show or hide Top Level Navigation" aria-controls="nav-primary" aria-expanded="false">
                    <svg class="open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M436 124H12c-6.627 0-12-5.373-12-12V80c0-6.627 5.373-12 12-12h424c6.627 0 12 5.373 12 12v32c0 6.627-5.373 12-12 12zm0 160H12c-6.627 0-12-5.373-12-12v-32c0-6.627 5.373-12 12-12h424c6.627 0 12 5.373 12 12v32c0 6.627-5.373 12-12 12zm0 160H12c-6.627 0-12-5.373-12-12v-32c0-6.627 5.373-12 12-12h424c6.627 0 12 5.373 12 12v32c0 6.627-5.373 12-12 12z"/></svg>
                    <svg class="close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><line x1="75" y1="75" x2="439" y2="439"/><line x1="439" y1="75" x2="75" y2="439"/></svg>
                    <span class="visually-hidden"><?php esc_html_e( 'Menu', 'dgwltd' ); ?></span>
                </button>

                <div class="dgwltd-nav__wrapper">

                <?php
                if ( has_nav_menu( 'primary' ) ) {
                    wp_nav_menu(
                        array(
                            'theme_location' => 'primary',
                            'menu_id'        => 'nav-primary',
                            'menu_class'     => 'dgwltd-menu',
                            'container'      => false,
                        )
                    );
                }
                ?>
                
                <?php 
                if(!empty($languages)) { 
                    include(locate_template( 'template-parts/_molecules/languages.php' ));
                } 
                ?>
                
                </div>
                
            </nav>
            
    </div><!--/container-->

</header>