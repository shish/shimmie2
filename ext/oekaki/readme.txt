    ChibiPaint

    Original version of ChibiPaint:
    Copyright (c) 2006-2008 Marc Schefer
    http://www.chibipaint.com/

    Some icons taken from the GNU Image Manipulation Program.
    Art contributors: http://git.gnome.org/browse/gimp/tree/AUTHORS
      Lapo Calamandrei
      Paul Davey
      Alexia Death
      Aurore Derriennic
      Tuomas Kuosmanen
      Karl La Rocca
      Andreas Nilsson
      Ville Pätsi
      Mike Schaeffer
      Carol Spears
      Jakub Steiner
      William Szilveszter    


    This file is part of ChibiPaint.

    ChibiPaint is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    ChibiPaint is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with ChibiPaint. If not, see <http://www.gnu.org/licenses/>.

  CHIBIPAINT
  
  ChibiPaint is an oekaki applet. A software that allows people to draw and paint online and share the result with
  other art enthusiasts. It's designed to be integrated with an oekaki board, a web server running dedicated software.
  Several are available but we don't currently provide an integrated solution for ChibiPaint.
  
  INTEGRATION
  
  ChibiPaint is still in the alpha stage of its development and the following integration specs are likely to evolve
  in the future.
  
  APPLET PARAMETERS
  
  Here's an example on how to integrate the applet in a html webpage
  
    <applet archive="chibipaint.jar" code="chibipaint.ChibiPaint.class" width="800" height="600">
      <param name="canvasWidth" value="400" />
      <param name="canvasHeight" value="300" />
      <param name="postUrl" value="http://yourserver/oekaki/cpget.php" />
      <param name="exitUrl" value="http://yourserver/oekaki/" />
      <param name="exitUrlTarget" value="_self" />
      <param name="loadImage" value="http://yourserver/oekaki/pictures/168.png" />
      <param name="loadChibiFile" value="http://yourserver/oekaki/pictures/168.chi" />
      JAVA NOT SUPPORTED! <!-- alternative content for users who don't have Java installed -->
    </applet>
  
  The parameters are:
  canvasWidth - width of the area on which users can draw (currently capped to 1024)
  canvasHeight - height of the area on which users can draw (currently capped to 1024)
  
  postUrl - url that will be used to post the resulting files (see below for more details)
  exitUrl - after sending the oekaki the user will be redirected to that url
  exitUrlTarget - optional target to allow different frames configuration
  
  loadImage - an image (png format) that will be loaded in the applet to be edited
  loadChibiFile - a chibifile format (.chi) multi-layer image that will be loaded in the applet to be edited

  NOTE: The last two parameters can be omited when they don't apply. If both loadImage and loadChibiFile are specified,
        loadChibiFile takes precedence
  
  POST FORMAT
  
  The applet will send the resulting png file and optionally a multi-layer chibifile format file.
  
  The files are sent as a regular multipart HTTP POST file upload, similar to the one used by form based file uploads
  for ease of processing by the server side script. 
  
  The form data name for the png file is 'picture' and 'chibifile' for the multilayer file. The recommended extension
  for chibifiles is '.chi'

  The applet expects the server to answer with the single line reply "CHIBIOK" followed by a newline character.
  
  "CHIBIERROR" followed by an error message on the same list is the planned way to report an error but currently the
  applet will just ignore the error message and report a failure on any reply except CHIBIOK.
  
  PHP EXAMPLE
  
  Here's an example of how a php script might handle the applet's POST
  
  <?php
    if (isset($_FILES["picture"]))
    {
      header ('Content-type: text/plain');

      $uploaddir = $_SERVER["DOCUMENT_ROOT"].'/oekaki/pictures/';
      $file = $_FILES['picture']['name'];
      $ext = (strpos($file, '.') === FALSE) ? '' : substr($file, strrpos($file, '.'));
      $uploadfile = $uploaddir . time();

      $success = TRUE;
      if (isset($_FILES["chibifile"]))
        $success = $success && move_uploaded_file($_FILES['chibifile']['tmp_name'], $uploadfile . ".chi");

      $success = $success && move_uploaded_file($_FILES['picture']['tmp_name'], $uploadfile . $ext);
      if ($success) {
        echo "CHIBIOK\n";
      } else {
        echo "CHIBIERROR\n";
      }
    }
    else
      echo "CHIBIERROR No Data\n";
  ?>
  
  CONTACT INFORMATION
  
  Author: Marc Schefer (codexus@codexus.com)
