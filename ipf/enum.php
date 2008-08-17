<?php

/*
Usage:

class Enum_Colors extends IPF_Enum {
    const RED = 'F00';
    const GREEN = '0F0';
    const BLUE = '00F';
}

function setColor( Enum_Colors $color ) {
    echo $color;
}
setColor( new Enum_Colors( 'GREEN' ) ); // will pass
setColor( '0F0' ); // won't pass
Enum_Colors::RED == new Enum_Colors( 'GREEN' ); // FALSE
Enum_Colors::RED == new Enum_Colors( 'RED' ); // TRUE

*/

abstract class IPF_Enum {
    private $current_val;

    final public function __construct( $type ) {
        $class_name = get_class( $this );

        $type = strtoupper( $type );
        if ( ! constant( "{$class_name}::{$type}" ) ) {
            throw new IPF_Exception_Enum( 'Not forund property "'.$type.'" in enum "'.$class_name.'"' );
        }
        $this->current_val = constant( "{$class_name}::{$type}" );
    }

    final public function __toString() {
        return $this->current_val;
    }
}
