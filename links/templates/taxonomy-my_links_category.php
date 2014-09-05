<?php
/**
 * The template for displaying Links Taxonomy.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 */

get_header(); ?>

		<?php if ( have_posts() ) : ?>

			<header class="page-header">

				<h2 class="archive-title">
					<?php
						if ( is_category() ) :
							single_cat_title();

						elseif ( is_tax() ) :
							single_term_title();

						elseif ( is_author() ) :
							printf( __( 'Author Archive: %s', 'zonediet' ), '<span class="vcard">' . get_the_author() . '</span>' );

						elseif ( is_day() ) :
							printf( __( 'Daily Archives: %s', 'zonediet' ), '<span>' . get_the_date() . '</span>' );

						elseif ( is_month() ) :
							printf( __( 'Monthly Archives: %s', 'zonediet' ), '<span>' . get_the_date( _x( 'F Y', 'monthly archives date format', 'zonediet' ) ) . '</span>' );

						elseif ( is_year() ) :
							printf( __( 'Yearly Archives: %s', 'zonediet' ), '<span>' . get_the_date( _x( 'Y', 'yearly archives date format', 'zonediet' ) ) . '</span>' );

						else :
							_e( 'Archives', 'zonediet' );

						endif;
					?>
				</h2>
				<?php
					// Show an optional term description.
					$term_description = term_description();
					if ( ! empty( $term_description ) ) :
						printf( '<div class="taxonomy-description">%s</div>', $term_description );
					endif;
				?>
			</header><!-- .page-header -->

			<?php while (have_posts()) : the_post(); ?>

					<article id="post-<?php the_ID(); ?>" <?php post_class('post-index clearfix'); ?> role="article">

						<header class="entry-header">

							<h3 class="entry-title"><?php the_title(); ?></h3>

							<?php
							/* Hidden by default
							<?php printf(__('Posted <time class="updated" datetime="%1$s" pubdate>%2$s</time> by <span class="author">%3$s</span> <span class="amp">&</span> filed under %4$s.', 'scaffolding'), get_the_time('Y-m-j'), get_the_time(get_option('date_format')), scaffolding_get_the_author_posts_link(), get_the_category_list(', ')); ?></p>
							*/ ?>

						</header> <!-- end article header -->

					</article> <!-- end article -->

				<?php endwhile; ?>

				<?php get_template_part('templates/template','pager'); //wordpress template pager/pagination ?>

			<?php else : ?>

                <?php get_template_part('templates/template','error'); //wordpress template error message ?>

			<?php endif; ?>


<?php get_footer();