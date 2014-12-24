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
						if ( is_tax() ) :
							single_term_title('Press Room: ');

						else :
							_e( 'Archives', 'scaffolding' );

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

            <ul class="press-link clearfix">

			<?php while (have_posts()) : the_post();

				$date = get_post_meta($post->ID, '_link-date', true);
                $url = get_post_meta($post->ID, "_link-url", true);
                $description = get_post_meta($post->ID, "_link-description", true);
                $target = get_post_meta($post->ID, "_link-target", true); ?>

				<li>
                <?php if ($date) {
                    echo sprintf(__('<time class="updated" datetime="%1$s" pubdate>%2$s</time>', 'scaffolding'), $date, $date);
                } ?>
                    <a href="<?php echo $url; ?>" title="<?php the_title(); ?>" target="<?php echo $target; ?>"><?php the_title(); ?></a>    
                <?php if ($description) { ?>
                    <div class="link-description"><?php echo $description; ?></div>
                <?php } ?>
                </li>    

            <?php endwhile; ?>
                
            </ul>

            <?php get_template_part('templates/template','pager'); //wordpress template pager/pagination ?>

			<?php else : ?>

                <?php get_template_part('templates/template','error'); //wordpress template error message ?>

			<?php endif; ?>


<?php get_footer();