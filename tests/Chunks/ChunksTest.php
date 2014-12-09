<?php
    
namespace radbasa\Chunks;

class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $chunks;
    
    protected function setUp() 
    {
        $format = array( 
                            array( 'name' => 'field1', 'size' => 4, 'callbacks' => array( ) ),
                            array( 'name' => 'field2', 'size' => 6, 'callbacks' => array( ) ),
                            array( 'name' => 'field3', 'size' => 2, 'callbacks' => array( ) ),
                            array( 'name' => 'field4', 'size' => 8, 'callbacks' => array( ) )
                        );
                        
        $this->chunks = new Chunks( $format );
    }
    
    public function testLength()
    {
        $this->assertEquals( 20, $this->chunks->getValidLength() );
    }

    // PARSE single record

    public function testLengthSingleRecord()
    {
        $record = '3984194374AA238501DF';
        $this->assertTrue( $this->chunks->hasValidLength( $record ) );

        // Check for proper lengths
        $this->setExpectedExceptionRegExp( 'UnexpectedValueException', '/Invalid Length.*/' );
        
        $record = '3984194374AA238501';
        $this->chunks->hasValidLength( $record );
        
        $record = '3984194374AA238501DFdfe';
        $this->chunks->hasValidLength( $record );
    }
    
    public function testLengthSingleRecordWithOptionalPadding()
    {
        $format = array( 
                            array( 'name' => 'field1', 'size' => 4, 'callbacks' => array( ) ),
                            array( 'name' => 'field2', 'size' => 6, 'callbacks' => array( ) ),
                            array( 'name' => 'field3', 'size' => 2, 'callbacks' => array( ) ),
                            array( 'name' => 'field4', 'size' => 8, 'callbacks' => array( ) ),
                            array( 'name' => 'padding', 'size' => 10, 'callbacks' => array( ), 'optionalpadding' => true )
                        );
        $this->chunks = new Chunks( $format );
        $record = '3984194374AA238501DF          ';
        
        $this->assertEquals( 20, $this->chunks->getValidLength() );
        $this->assertEquals( 10, $this->chunks->getOptionalLength() );
    }

    public function testLengthSingleRecordWithOptionalPaddingWrongLocation()
    {
        $this->setExpectedException( 'InvalidArgumentException', 'Optionalpadding not in the last element' );

        $format = array( 
                            array( 'name' => 'field1', 'size' => 4, 'callbacks' => array( ) ),
                            array( 'name' => 'field2', 'size' => 6, 'callbacks' => array( ) ),
                            array( 'name' => 'field3', 'size' => 2, 'callbacks' => array( ) ),
                            array( 'name' => 'field4', 'size' => 8, 'callbacks' => array( ), 'optionalpadding' => true ),
                            array( 'name' => 'padding', 'size' => 10, 'callbacks' => array( ) )
                        );
        $this->chunks = new Chunks( $format );
    }

    public function testParseSingleRecord()
    {
        $record = '3984194374AA238501DF';
        $parsed = $this->chunks->parse( $record );
                
        $this->assertEquals( '3984', $parsed[ 0 ][ 'field1' ] );
        $this->assertEquals( '194374', $parsed[ 0 ][ 'field2' ] );
        $this->assertEquals( 'AA', $parsed[ 0 ][ 'field3' ] );
        $this->assertEquals( '238501DF', $parsed[ 0 ][ 'field4' ] );         
    }
    
    public function testParseSingleRecordWithOptionalPadding()
    {
        $format = array( 
                            array( 'name' => 'field1', 'size' => 4, 'callbacks' => array( ) ),
                            array( 'name' => 'field2', 'size' => 6, 'callbacks' => array( ) ),
                            array( 'name' => 'field3', 'size' => 2, 'callbacks' => array( ) ),
                            array( 'name' => 'field4', 'size' => 8, 'callbacks' => array( ) ),
                            array( 'name' => 'padding', 'size' => 10, 'callbacks' => array( ), 'optionalpadding' => true )
                        );
        $this->chunks = new Chunks( $format );
        $record = '3984194374AA238501DF          ';
        
        $parsed = $this->chunks->parse( $record );
        
        $this->assertEquals( '3984', $parsed[ 0 ][ 'field1' ] );
        $this->assertEquals( '194374', $parsed[ 0 ][ 'field2' ] );
        $this->assertEquals( 'AA', $parsed[ 0 ][ 'field3' ] );
        $this->assertEquals( '238501DF', $parsed[ 0 ][ 'field4' ] );    
        
        // Length exception
        $this->setExpectedExceptionRegExp( 'UnexpectedValueException', '/Invalid Length.*/' );
        
        $record = '3984194374AA238501DF           ';     // too much padding
        $this->chunks->parse( $record );
    }    
    
    // PARSE multiple records
    
    public function testLengthMultipleRecord()
    {
        $records = '3984194374AA238501DF28457FD4092356649234';
        $this->assertTrue( $this->chunks->hasValidLength( $records ) );
        
        $this->setExpectedExceptionRegExp( 'UnexpectedValueException', '/Invalid Length.*/' );
        
        $records = '3984194374AA238501DF28457FD4092356649234GH';
        $this->chunks->hasValidLength( $records );
        
        $records = '3984194374AA238501DF28457FD40923566492';
        $this->chunks->hasValidLength( $records );
    }
    
    public function testParseMultipleRecords()
    {
        $records = '3984194374AA238501DF28457FD409GH566492344562349872QQ4FGE6456';
        $parsed = $this->chunks->parse( $records );
        
        $this->assertEquals( '3984', $parsed[ 0 ][ 'field1' ] );
        $this->assertEquals( '194374', $parsed[ 0 ][ 'field2' ] );
        $this->assertEquals( 'AA', $parsed[ 0 ][ 'field3' ] );
        $this->assertEquals( '238501DF', $parsed[ 0 ][ 'field4' ] );
        $this->assertEquals( '2845', $parsed[ 1 ][ 'field1' ] );
        $this->assertEquals( '7FD409', $parsed[ 1 ][ 'field2' ] );
        $this->assertEquals( 'GH', $parsed[ 1 ][ 'field3' ] );
        $this->assertEquals( '56649234', $parsed[ 1 ][ 'field4' ] );  
        $this->assertEquals( '4562', $parsed[ 2 ][ 'field1' ] );
        $this->assertEquals( '349872', $parsed[ 2 ][ 'field2' ] );
        $this->assertEquals( 'QQ', $parsed[ 2 ][ 'field3' ] );
        $this->assertEquals( '4FGE6456', $parsed[ 2 ][ 'field4' ] );        
    }
    
    public function testParseMultipleRecordsWithOptionalPadding()
    {
        // Should throw exception
        $format = array( 
                            array( 'name' => 'field1', 'size' => 4, 'callbacks' => array( ) ),
                            array( 'name' => 'field2', 'size' => 6, 'callbacks' => array( ) ),
                            array( 'name' => 'field3', 'size' => 2, 'callbacks' => array( ) ),
                            array( 'name' => 'field4', 'size' => 8, 'callbacks' => array( ) ),
                            array( 'name' => 'padding', 'size' => 10, 'callbacks' => array( ), 'optionalpadding' => true )
                        );
        $this->chunks = new Chunks( $format );

        $this->setExpectedExceptionRegExp( 'UnexpectedValueException', '/Invalid Length.*/' );
        
        $records = '3984194374AA238501DF28457FD409GH566492344562349872QQ4FGE6456';
        $parsed = $this->chunks->parse( $records );
    }
    
    // ASSEMBLE records
    
    public function testAssembleNotArrayInput()
    {
        $this->setExpectedException( 'InvalidArgumentException', 'Must be an array' );
        
        $input = 'gaerohargaherasdfiurehg';
        
        $this->chunks->assemble( $input );
    }
    
    public function testAssemble()
    {
        $input = array(
                    array( 'field1' => 3984,
                            'field2' => '194374',
                            'field3' => 'AA',
                            'field4' => '238501DF' ),
                    array( 'field1' => 2845,
                            'field2' => '7FD409',
                            'field3' => 'GH',
                            'field4' => '56649234' ),
                    array( 'field1' => 4562,
                            'field2' => '349872',
                            'field3' => 'QQ',
                            'field4' => '4FGE6456' )
        );
        
        $this->assertEquals( '3984194374AA238501DF28457FD409GH566492344562349872QQ4FGE6456', $this->chunks->assemble( $input ) );
    }

    public function testAssembleMissingKeys()
    {
        $input = array(
                    array( 'field1' => 3984,
                            'field2' => '194374',
                            'field3' => 'AA',
                            'field4' => '238501DF' ),
                    array( 'field1' => 2845,
                            'field2' => '7FD409',
                            'field4' => '56649234' ),
                    array( 'field1' => 4562,
                            'field2' => '349872',
                            'field3' => 'QQ',
                            'field4' => '4FGE6456' )
        );
        
        $this->setExpectedException( 'UnexpectedValueException', 'Invalid key' );
        
        $this->chunks->assemble( $input );
    }

    public function testAssembleExtraKeys()
    {
        $input = array(
                    array( 'field1' => 3984,
                            'field2' => '194374',
                            'field3' => 'AA',
                            'field4' => '238501DF' ),
                    array( 'field1' => 2845,
                            'field2' => '7FD409',
                            'field3' => 'GH',
                            'field4' => '56649234',
                            'field5' => 'asgwe' ),
                    array( 'field1' => 4562,
                            'field2' => '349872',
                            'field3' => 'QQ',
                            'field4' => '4FGE6456' )
        );
        
        $this->setExpectedException( 'UnexpectedValueException', 'Invalid keys' );
        
        $this->chunks->assemble( $input );
    }
    
    public function testSingleCallbackFunction()
    {
        
    }
    
    public function testMultipleCallbackFunctions()
    {
        
    }
    
    protected function tearDown()
    {
        unset( $this->chunks );
    }
}