<?php
/********************************cron**************************************/
/*
$new_fecha = date("d-m-Y",strtotime($expire."+ 1 week"));
update_post_meta($new_post_id,'periodo_plan_final',$new_fecha);
update_post_meta($new_post_id,'estatus','En espera');
update_post_meta($new_post_id, 'clon', 0 );
*/
/*****************************cron actividades programadas*************************/
function check_job_end_date() {

  global $post;

  $args = array(
  	'post_type'       => 'actividad',
  	'posts_per_page'  => -1,
  );

  $listings = get_posts( $args );
	foreach($listings as $post) : setup_postdata($post);

  $today = date( 'Ymd' );
  $old_id = get_the_ID();
   $tipo = get_post( $old_id );
    $titulo = 'New '.get_the_title($old_id);

    $bandera = get_post_meta($old_id, 'clon', TRUE);
    $cron = get_post_meta($old_id, 'cron', TRUE);

    $plan_inicio = get_post_meta($old_id, 'fecha_plan_inicio', TRUE);
    $expire = get_post_meta($old_id, 'fecha_plan_final', TRUE);
  	$real_inicio = get_post_meta($old_id, 'Fecha_real_inicio', TRUE);
    $real_final = get_post_meta($old_id, 'fecha_real_final', TRUE);
    $status = get_post_meta($old_id, 'estatus', TRUE );
	if($cron == 1 )
  {
    if ( $expire < $today & $bandera == 0 ) :
      $status = 'Terminada';
      $new_pf = date("Ymd",strtotime($expire."+ 1 week"));
      $new_pi = date("Ymd",strtotime($plan_inicio."+ 1 week"));
      $new_rf = date("Ymd",strtotime($real_final."+ 1 week"));
      $new_ri = date("Ymd",strtotime($real_inicio."+ 1 week"));
        if($new_pf >= $today & $status == 'Terminada' & $bandera == 0)
        {

          $new_post_id = wp_insert_post( array(
            'post_status'	 => 'publish',
            'menu_order'     => $tipo->menu_order,
            'comment_status' => $tipo->comment_status,
            'ping_status'    => $tipo->ping_status,
            'post_author'    => $tipo->post_author,
            'post_content'   => $tipo->post_content,
            'post_excerpt'   => $tipo->post_excerpt,
            'post_title'     => $titulo,
            'post_type'      => $tipo->post_type,
          ));

          $post_meta = apply_filters( 'Clone_post_meta', get_post_meta( $old_id ), $new_post_id, $old_id );

            $ignored_meta = apply_filters( 'Clone_ignored_meta', array(
              '_edit_lock',
              '_edit_last',
              '_wp_old_slug',
              '_wp_trash_meta_time',
              '_wp_trash_meta_status',
              '_previous_revision',
              '_wpas_done_all',
              '_encloseme',
              '_cr_original_post',
              '_cr_replace_post_id',
              '_cr_replacing_post_id'
            ) );

            if ( empty( $post_meta ) )
              return;

            foreach ( $post_meta as $key => $value_array ) {
              if ( in_array( $key, $ignored_meta ) )
                continue;

              foreach ( (array) $value_array as $value ) {
                add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
              }
            }
            update_post_meta($new_post_id, 'estatus' , 'En espera' );
            update_post_meta($new_post_id, 'fecha_plan_inicio' , $new_pi );
            update_post_meta($new_post_id, 'fecha_plan_final' , $new_pf );
            update_post_meta($new_post_id, 'Fecha_real_inicio' ,  $new_ri);
            update_post_meta($new_post_id, 'fecha_real_final' , $new_rf );
            update_post_meta($new_post_id, 'cron' , 1 );
            update_post_meta($new_post_id, 'clon' , 0 );
        }
        update_post_meta($old_id, 'estatus', $status );
        update_post_meta($old_id, 'clon', 1 );
    endif;
  }
	endforeach;

}

// Schedule Cron Job Event
	wp_schedule_event( date( 'Ymd' ), 'daily', 'job_listing_cron_job' );
add_action( 'job_listing_cron_job', 'check_job_end_date' );

/*****************************fin actividades programadas***************************************/
/*********************************cron para validar actividades*********************************/

function actividades_atrasadas()
{
  wp_reset_query();
  $today = date( 'Ymd' );
  $cont_act_atr = 0;
    $ejecutando = 0;
  $args = array(
      'post_type'       => 'actividad',
      'posts_per_page'  => -1,
    );
  $loop = new WP_Query($args);
  if($loop->have_posts()) {
    while($loop->have_posts()) : $loop->the_post();
    $id_a = get_the_ID();
    $funcRel = get_post_meta($id_a, 'relacion_funcion', true);
    $estatus = get_post_meta($id_a, "estatus", true);
    $fecha = get_post_meta($id_a, "fecha_plan_final", true);
    $avance = get_post_meta($id_a, "avance", true);
    $puntos = get_post_meta($id_a, "puntos", true);
    if($avance == $puntos){
      update_post_meta($id_a, "estatus", "Terminada");
    }
    if($estatus != "Terminada" && $fecha < $today )
    {
        $cont_act_atr = $cont_act_atr + 1;
    }
    if($estatus == "En proceso")
    {
      $ejecutando = $ejecutando + 1;
    }
    endwhile;
  }
  update_post_meta($funcRel, 'en_ejecucion' ,$ejecutando);
  update_post_meta($funcRel, 'actividades_atrasadas' , $cont_act_atr);
}

// Schedule Cron Job Event
	wp_schedule_event( date( 'Ymd' ), 'daily', 'cron_act_atr' );
add_action( 'cron_act_atr', 'actividades_atrasadas' );
/*****************************fin cron para validar actividades*********************************/
