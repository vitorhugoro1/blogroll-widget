<?php
/*
Plugin Name: Blogroll
Version: 0.1
Description: Criar um Widget e Shortcode para adicionar um lista de blogs.
Author: Vitor Hugo Rodrigues Merencio
License: GPL2
*/

class blogroll_plugin extends WP_Widget {

	// constructor
	function blogroll_plugin() {
    parent::WP_Widget(false, $name = 'Blogroll' );
    add_action( 'wp_enqueue_scripts', array( $this, 'style' ) );
    add_action( 'init', array($this, 'post_type' ));
    add_action( 'add_meta_boxes', array($this, 'meta_box') );
    add_action( 'save_post', array($this, 'meta_box_saved') );
	}

	// widget form creation
	function form($instance) {
    if($instance){
     $title = esc_attr($instance['title']);
     $qtd = esc_attr($instance['qtd']);
   } else {
     $title = 'Blogroll';
     $qtd = 9;
   }

   ?>
     <p>
       <label for="<?php echo $this->get_field_id('title'); ?>"><?php echo 'Titulo'; ?></label>
       <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"  name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>">
     </p>
     <p>
       <label for="<?php echo $this->get_field_id('qtd'); ?>"><?php echo 'Quantidade de blogs'; ?></label>
       <select class="widefat" id="<?php echo $this->get_field_id('qtd'); ?>"  name="<?php echo $this->get_field_name('qtd'); ?>">
         <option value="3" <?php selected( 3 , $qtd); ?>>3</option>
         <option value="6" <?php selected( 6 , $qtd); ?>>6</option>
         <option value="9" <?php selected( 9 , $qtd); ?>>9</option>
       </select>
     </p>
   <?php
	}

  function style() {
    wp_enqueue_style(
      'blogroll-widget',
      plugins_url('blogroll-widget.css', __FILE__),
      array(),
      false,
      false
    );
  }

  // widget update
	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);
		$instance['qtd'] = strip_tags($new_instance['qtd']);

		return $instance;
	}

	// widget display
	function widget($args, $instance) {
		extract($args);

		$blogroll = get_posts(array('post_type' => 'blogroll', 'orderby' => 'rand', 'post_status' => 'publish', 'posts_per_page' => $instance['qtd']));

		$title = apply_filters('widget_title', $instance['title'] );

		$widget = $before_widget;

		if ( $title ) {
		$widget .= $before_title . $title . $after_title;
	    }

		$widget .= '<div class="blogroll">';

		if(count($blogroll) > 0){
			$widget .= '<ul class="blogroll-list">';

			foreach($blogroll as $blog){
				$widget .= '<li class="blogroll-item">';
					$widget .= '<a href="' . esc_url(get_post_meta($blog->ID, '_vhr_url', true)) .'" target="_blank">';
					$widget .= "<img src='" . get_the_post_thumbnail_url($blog->ID, 'full') . "' title='" . get_the_title( $blog->ID ) . "'>";
					$widget .= '</a>';
				$widget .= '</li>';
			}

			$widget .= '</ul>';
		}

		$widget .= '<div>';

		echo $widget;
	}

  function post_type() {
    $labels = array(
      'name'                => __( 'Blogroll', 'text-domain' ),
      'singular_name'       => __( 'Blogroll', 'text-domain' ),
      'add_new'             => _x( 'Adicionar novo blog', 'text-domain', 'text-domain' ),
      'add_new_item'        => __( 'Adicionar novo blog', 'text-domain' ),
      'edit_item'           => __( 'Editar blog', 'text-domain' ),
      'new_item'            => __( 'Novo blog', 'text-domain' ),
      'view_item'           => __( 'Ver blog', 'text-domain' ),
      'search_items'        => __( 'Procurar blogs', 'text-domain' ),
      'not_found'           => __( 'Nenhum blog encontrado', 'text-domain' ),
      'not_found_in_trash'  => __( 'Nenhum blog encontrado no lixo', 'text-domain' ),
      'parent_item_colon'   => __( 'Blog pai:', 'text-domain' ),
      'menu_name'           => __( 'Blogroll', 'text-domain' ),
    );

    $args = array(
      'labels'                   => $labels,
      'hierarchical'        => false,
      'taxonomies'          => array(),
      'public'              => false,
      'show_ui'             => true,
      'show_in_menu'        => true,
      'show_in_admin_bar'   => true,
      'menu_position'       => null,
      'menu_icon'           => null,
      'show_in_nav_menus'   => true,
      'publicly_queryable'  => false,
      'exclude_from_search' => false,
      'has_archive'         => false,
      'query_var'           => false,
      'can_export'          => true,
      'rewrite'             => true,
      'capability_type'     => 'post',
      'supports'            => array('title', 'thumbnail' )
    );

    register_post_type( 'blogroll', $args );
  }

  function meta_box() {
    add_meta_box( 'blogroll-url', 'Caminho do blog', array($this, 'meta_box_field'), 'blogroll', 'normal', 'high');
  }

  function meta_box_field() {
    $custom = get_post_custom( $post->ID );

    $url = isset($custom['_vhr_url']) ? esc_url($custom['_vhr_url'][0]) : '';

    wp_nonce_field('my_blogroll_url', 'blogroll_url');
    ?>
      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="_vhr_url">Caminho do blog</label>
          </th>
          <td>
            <input class="regular-text" type="text" name="_vhr_url" id="_vhr_url" placeholder="Url do blog" value="<?=$url?>" required>
            <p class="description">Exemplo http://poxanine.com.br</p>
          </td>
        </tr>
      </table>
    <?php
  }

  function meta_box_saved($post_id) {
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    if( !isset( $_POST['blogroll_url'] ) || !wp_verify_nonce( $_POST['blogroll_url'], 'my_blogroll_url' ) ) return;

    if( !current_user_can( 'edit_post' ) ) return;

    update_post_meta( $post_id, '_vhr_url', $_POST['_vhr_url'] );
  }
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("blogroll_plugin");'));
