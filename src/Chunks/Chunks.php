<?php
namespace radbasa\Chunks;

class Chunks
{
    protected $format;
    protected $length;
    protected $optional;
    protected $single_with_optional_padding;
    
    public function __construct( $format )
    {
        $this->format = $format;
        $this->setLengths( $format );
    }
    
    public function getValidLength()
    {
        return $this->length;
    }
    
    public function getOptionalLength()
    {
        return $this->optional;
    }
    
    public function hasValidLength( $record )
    {
        return strlen( $record ) % $this->length == 0 ? true : false;
    }
    
    public function parse( $input )
    {
        // test if optinoal padded
        if ( !$this->hasValidLength( $input ) ) {
            // throw Exception
            return false;
        }
                
        $records = strlen( $input ) / $this->length;
        
        if ( $this->single_with_optional_padding && $records != 1 ) {
            // throw Exception
            return false;
        }

        $output_array = array();

        for ( $i = 0; $i < $records; $i++ ) {
            $this_array = array();
            
            foreach ( $this->format as $fieldformat ) {
                if ( $this->single_with_optional_padding && array_key_exists( 'optionalpadding', $fieldformat ) ) {
                    
                } else {
                    $this_array += array( $fieldformat[ 'name' ] => substr( $input, 0, $fieldformat[ 'size' ] ) );
                    $input = substr( $input, $fieldformat[ 'size' ] );
                }
            }
            
            array_push( $output_array, $this_array );
        }
        
        return $output_array;
    }
    
    private function setLengths( $format )
    {
        $length = 0;
        $format_entries = count( $format );
        foreach ( $format as $index => $fieldformat ) {
            if ( array_key_exists( 'optionalpadding', $fieldformat ) && $fieldformat[ 'optionalpadding' ] == true ) {
                if ( $index < $format_entries - 1 ) {
                    // throw Exception
                    return false;   
                } else {
                    $this->optional = $fieldformat[ 'size' ];   
                    $this->single_with_optional_padding = true;
                }
            }
            else
                $length += $fieldformat[ 'size' ];    
        }
        
        $this->length = $length;
    }
}