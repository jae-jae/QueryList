<?php
/**
 * Example script for phpQuery Script plugin
 *
 * Avaible are 4 variables:
 * - $self Represents $this
 * - $params Represents parameters passed to script() method (without script name)
 * - $return If not null, will be used as method result
 * - $config Content of __config.php file
 *
 * By default each script returns $self aka $this.
 */
$return = $self->find($params[0]);
?>