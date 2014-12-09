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
    
    public function getRecordCount()
    {
        return $this->record_count;
    }
    
    public function hasValidLength( $record )
    {   
        $record_length = strlen( $record );
        if ( !$this->single_with_optional_padding && $record_length % $this->length == 0 ||
              $this->single_with_optional_padding && $record_length >= $this->length && $record_length <= $this->length + $this->optional )
            return true;
        else
            throw new \UnexpectedValueException( 'Invalid Length' );
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
}