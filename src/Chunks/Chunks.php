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
        $record_length = strlen( $record );
        $padded_length = $this->length + $this->optional;
        
        if ( !$this->single_with_optional_padding && $record_length % $this->length == 0 ||
              $this->single_with_optional_padding && $record_length >= $this->length && $record_length <= $padded_length ) {
            return true;
        }
        else {
            $message = 'Expected ' . $this->length . ' with optional ' . $this->optional . '. Received ' . $record_length . '.';
            
            if ( $this->single_with_optional_padding && $record_length > $padded_length )
                $message .= ' You might be trying to parse multiple records with a single record format.';
            
            throw new \UnexpectedValueException( 'Invalid Length. ' . $message );
        }
        
        return;
    }
    
    public function parse( $input )
    {
        $this->hasValidLength( $input );

        $records = strlen( $input ) / $this->length;
        
        if ( $this->single_with_optional_padding )
            $records = 1;
        else
            $records = strlen( $input ) / $this->length;

        $output_array = array();

        for ( $i = 0; $i < $records; $i++ ) {
            $this_array = array();
            
            foreach ( $this->format as $fieldformat ) {
                if ( !( $this->single_with_optional_padding && array_key_exists( 'optionalpadding', $fieldformat ) ) ) {
                    $this_value = substr( $input, 0, $fieldformat[ 'size' ] );
                    // perform callback functions here
                    $this_array += array( $fieldformat[ 'name' ] => $this_value );
                    $input = substr( $input, $fieldformat[ 'size' ] );          
                }
            }
            
            array_push( $output_array, $this_array );
        }
        
        return $output_array;
    }
    
    public function assemble( $input )
    {
        $this->checkKeys( $input );
        
        $output = '';
        foreach ( $input as $entry ) {
            foreach( $this->format as $format_item ) {
                if ( array_key_exists( $format_item[ 'name' ], $entry ) ) {
                    $append_this = $entry[ $format_item[ 'name' ] ];
                    // perform callback functions here
                    if ( $format_item[ 'size' ] == strlen( $append_this ) )
                        $output .= $append_this;
                    else
                        throw new \InvalidArgumentException( 'Wrong field length' );
                } else {
                    throw new \UnexpectedValueException( 'Invalid key' );
                }
            }
        }
        
        return $output;
    }
    
    private function setLengths( $format )
    {
        $length = 0;
        $format_entries = count( $format );
        foreach ( $format as $index => $fieldformat ) {
            if ( array_key_exists( 'optionalpadding', $fieldformat ) && $fieldformat[ 'optionalpadding' ] == true ) {
                if ( $index < $format_entries - 1 ) {
                    throw new \InvalidArgumentException( 'Optionalpadding not in the last element' );
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
    
    private function checkKeys( $input )
    {
        if ( !is_array( $input ) )
            throw new \InvalidArgumentException( 'Must be an array' );
            
        $input_keys = array();
        foreach ( $input as $entry ) {
            foreach ( $entry as $key => $value ) {
                if ( !in_array( $key, $input_keys ) )
                    array_push( $input_keys, $key );
            }
        }
                       
        $format_keys = array();
        foreach( $this->format as $entry )
            array_push( $format_keys, $entry[ 'name' ] );
        
        if ( $input_keys != $format_keys )
            throw new \UnexpectedValueException( 'Invalid keys' );
        
        return;
    }
}