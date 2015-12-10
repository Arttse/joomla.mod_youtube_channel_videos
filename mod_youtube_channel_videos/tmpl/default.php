<?php
/**
 * @copyright      Copyright (C) 2015 Nikita «Arttse» Bystrov. All rights reserved.
 * @license        License GNU General Public License version 3
 * @author         Nikita «Arttse» Bystrov
 */

defined ( '_JEXEC' ) or die;

// $h = $helper

/** @var null|array - Data of YouTube Videos */
$items = $h->getItems ();

if ( !empty( $items ) ) : ?>

    <div class="items">
        <?php foreach ( $items as $item ) :
            $title = isset( $item->snippet->title ) ? $item->snippet->title : '';
            $description = isset( $item->snippet->description ) ? $item->snippet->description : '';
            $videoId = isset( $item->snippet->resourceId->videoId ) ? $item->snippet->resourceId->videoId : '';
            ?>

            <div class="item">

                <?php if ( !empty( $videoId ) ) : ?>
                    <div class="video">
                        <iframe src="https://www.youtube.com/embed/<?php echo $videoId ?>"></iframe>
                    </div>
                <?php endif; ?>

                <?php if ( !empty( $title ) ) : ?>
                    <div class="title"><?php echo $title ?></div>
                <?php endif ?>

                <?php if ( !empty( $description ) ) : ?>
                    <div class="description"><?php echo $description ?></div>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>
    </div>

<?php endif; ?>