<?php

class Custom_Posts_Route extends WP_REST_Controller {

  public function register_routes ()
  {
    $namespace = 'wp/v2/posts';
    register_rest_route($namespace, '/blocks/(?P<id>\d+)', [
      array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array($this, 'get_item'),
      ),
    ]);
  }

  /**
   * Get a collection of items
   *
   * @param WP_REST_Request $request Full data about the request.
   * @return WP_Error|WP_REST_Response
   */
  public function get_item ($request)
  {
    global $post;
    $post = get_post( $request[ 'id' ] );
    $data = [];

    if ( $post ) {


        $blocks = parse_blocks( $post->post_content );

        foreach ($blocks as $key => $block) {
            if ( $block[ 'blockName' ] === 'core/heading' ) {
                $data[] = [
                    'name' => 'Heading',
                    'text' => wp_strip_all_tags( $block[ 'innerHTML' ] ),
                ];
            }
            if ( $block[ 'blockName' ] === 'core/image' ) {
                if ( $image_id = $block[ 'attrs' ][ 'id' ] ) {

                    // acquiring figcaption data for image caption
                    $element = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $element->loadHTML( $block[ 'innerHTML' ] );
                    libxml_clear_errors();
                    $selector = new DOMXPath($element);
                    $figcaption = $selector->query('//figcaption');

                    if ( !is_null( $figcaption ) ) {
                        foreach( $figcaption as $result ) {
                            $nodes = $result->childNodes;
                            foreach ($nodes as $node) {
                                $caption_data = $node->nodeValue;
                            }
                        }
                    }

                    $data[] = [
                        'name' => 'Image',
                        'src' => wp_get_attachment_image_src( $image_id )[ 0 ],
                        'alt' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ?: '',
                        'caption' => $caption_data ?: '',
                    ];


                }
            }

            if ( $block[ 'blockName' ] === 'core/paragraph' ) {
                // prevent inserting of empty paragraph block
                if ( ! empty( wp_strip_all_tags( $block[ 'innerHTML' ] ) ) ) {
                    $data[] = [
                        'name' => 'Paragraph',
                        'text' => wp_strip_all_tags( $block[ 'innerHTML' ] ),
                    ];
                }
            }

        }

    }

    return new WP_REST_Response($data, 200);
  }
}

add_action('rest_api_init', function () {
  $controller = new Custom_Posts_Route();
  $controller->register_routes();
});