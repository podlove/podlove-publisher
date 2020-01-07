<?php
/*! This file implements several classes to calculate and display a linear
* regression to compare episodes to each other. The regression is calculated
* based on the download stats of each episode after 2 weeks.
*
* To use it, copy this file into the podlove-publisher directory into the
* folder lib/settings.
* Insert the following code into the file analytics.php, e.g. after the
* downloads chart, approx. at line number 305:
* <?php require("regression.php"); new PodloveRegression(array()); ?>
*
* @auther Bernhard R. Fischer, <bf@abenteuerland.at>
* @date 2020/01/06
* @version 2.0
 */

namespace Podlove\Settings;

use \Podlove\Model;


/*! The class Regression is a class to calculate a linear regression of an
   * 1-dimensional array of numbers.
 */
class Regression
{
   //! internal calculation state, new
   const REG_NEW = 0;
   //! internal calculation state, regression parameters ready
   const REG_PARAM = 1;
   //! internal calculation state, regression ready
   const REG_CALC = 2;
   //! variable to keep internal state of calculation
   protected $calc_state = Regression::REG_NEW;

   //! maximum download value
   protected $max_dn_ = 0;
   //! number of elements in array
   protected $cnt_ = 0;
   //! data array
   protected $dat_ = array();
   //! regression parameter b0
   protected $b0_ = 0;
   //! regression parameter b1
   protected $b1_ = 0;
   //! determination
   protected $r2_ = 0;
   //! max regression value
   protected $max_reg_ = 0;


   /*! Object constructur.
    * @param $dat 1-dimensional array of numbers.
    */
   function __construct($dat)
   {
      $this->max_dn_ = max($dat);
      $this->cnt_ = count($dat);
      $this->dat_ = array('val' => $dat);
   }


   /*! This method calculates the regression parameters b0 and b1.
    */
   function reg_calc_param()
   {
      $sum = array_sum($this->dat_['val']);
      $xsum = $this->cnt_ * ($this->cnt_ - 1) / 2;

      $avg_ep = $xsum / $this->cnt_;
      $avg_dn = $sum / $this->cnt_;

      for ($i = 0; $i < $this->cnt_; $i++)
      {
         $this->dat_['xx'][$i] = $i - $avg_ep;
         $this->dat_['yy'][$i] = $this->dat_['xy'][$i] = $this->dat_['val'][$i] - $avg_dn;
         $this->dat_['xy'][$i] *= $this->dat_['xx'][$i];
         $this->dat_['xx'][$i] *= $this->dat_['xx'][$i];
         $this->dat_['yy'][$i] *= $this->dat_['yy'][$i];
      }

      $xy_sum = array_sum($this->dat_['xy']);
      $xx_sq = array_sum($this->dat_['xx']);

      $this->b1_ = $xy_sum / $xx_sq;
      $this->b0_ = $avg_dn - $this->b1_ * $avg_ep;

      $this->calc_state = Regression::REG_PARAM;
   }


   /*! This method calculates the regression values to each point based on the
     * regression parameters b0 and b1.
     * The method automatically calls reg_calc_param() if it was not called
     * before.
    */
   function reg_calc()
   {
      if ($this->calc_state == Regression::REG_NEW)
         $this->reg_calc_param();

      $this->dat_['rval'] = array();
      for ($i = 0; $i < $this->cnt_; $i++)
      {
         $this->dat_['rval'][$i] = $this->b0_ + $this->b1_ * $i;
         $this->dat_['dy2'][$i] = $this->dat_['dev'][$i] = $this->dat_['val'][$i] - $this->dat_['rval'][$i];
         $this->dat_['dy2'][$i] *= $this->dat_['dy2'][$i];
         if ($this->dat_['rval'][$i])
            $this->dat_['p'][$i] = $this->dat_['dev'][$i] / $this->dat_['rval'][$i];
      }

      $yy_sum = array_sum($this->dat_['yy']);
      $dy2_sum = array_sum($this->dat_['dy2']);
      if ($yy_sum > 0)
         $this->r2_ = 1 - $dy2_sum / $yy_sum;

      $this->max_reg_ = max($this->dat_['rval']);
      $this->calc_state = Regression::REG_CALC;
   }


   /*! This method returns the array of regression values (y-values to each
      * point). The function automatically calls reg_calc() if it wasn't called
      * already.
      * @return Returns an array of values.
    */
   function rval()
   {
      if ($this->calc_state != Regression::REG_CALC)
         $this->reg_calc();

      return $this->dat_['rval'];
   }


   /*! This method returns the array of internal values. This is the same as
      * was initialized with the constructor.
      * @return Returns an array of values.
    */
   function val()
   {
      return $this->dat_['val'];
   }


   /*! This function returns an array with deviation values. This is the
      * difference between the regression value and the actual value.
      * @return Returns an array of values.
    */
   function dev()
   {
      return $this->dat_['dev'];
   }


   /*! This function returns an array with deviation in percentage. This is how
      * many percent is the deviation of the regression.
    */
   function p()
   {
      return $this->dat_['p'];
   }


   /*! This method returns the element count, i.e. how many array elements are
    * there.
    * @return Number of elements in the array.
    */
   function count()
   {
      return $this->cnt_;
   }


   /*! This method returns the maximum value of both arrays, the original
      * values and the regression values.
      * @return Maximum y value on y-axis.
    */
   function max()
   {
      return max($this->max_dn_, $this->max_reg_);
   }


   /*! This function returns the regression parameters as an array.
      * yi = b0 + b1 * xi
      * @return Associative array with elements 'b0', 'b1', and 'r2'.
    */
   function rparam()
   {
      return array('b0' => $this->b0_, 'b1' => $this->b1_, 'r2' => $this->r2_);
   }
}


/*! The class JSRegression creates the regression dataset as JS object and
   * includes the JS code which draws the diagram based on the dataset.
 */
class JSRegression
{
   //! maximum number of dynamic y-axis grid
   const MAX_GRID_LINES = 12;
   //! grid line factor
   const MUL_OF = 5;

   //! internal Regression object
   protected $reg_;
   //! first episode (array index) to display on diagram
   protected $start_ = 0;
   //! number of episodes to display
   protected $cnt_;
   //! distance of y-grid lines
   protected $ystep_ = JSRegression::MUL_OF;
   //! descriptional data of elements
   protected $desc_ = array();
 

   /*! Object constructor.
    * @param $reg object of type Regression.
    * @param $vis Optional parameter for max number of visible elements.
    */
   function __construct($reg, $vis = 0)
   {
      $this->reg_ = $reg;

      if ($vis <= 0)
      {
         $this->cnt_ = $this->reg_->count();
      }
      else
      {
         if ($vis > $this->reg_->count())
            $vis = $this->reg_->count();
         $this->cnt_ = $vis;
         $this->start_ = $this->reg_->count() - $vis;
      }

      // calculate distance grid lines along the y-axis
      $this->ystep_ = round($this->reg_->max() / JSRegression::MAX_GRID_LINES / JSRegression::MUL_OF + 1) * JSRegression::MUL_OF;
   }


   //! Set description array.
   /*! This method sets the array which contains the description (e.g. title) of the dataset.
    * Each entry shall contain an associative array with at least the element 'title'.
    * @param $a Array with descriptive entries.
    */
   function set_description($a)
   {
      $this->desc_ = $a;
   }


   //! Method to make percentage string.
   /*! This method creates a percentage string based on the parameter.
    * @param $i Value to format, -1 <= $i <= 1.
    * @return Returns a string of format [+-]N.F% where N is $i*100.
    */
   protected function px($i)
   {
      $p = $this->reg_->p()[$i] * 100;
      if (abs($p) >= 10)
        $p = round($p);
      else
        $p = round($p, 1);

      if ($p >= 0)
         $p = "+$p";

      return "$p%";
   }


   //! Output HTML code.
   function html()
   {
      ?>
      <div id="reg_cv_container" style="position:relative;width:100%;height:256px;">
         <canvas id="reg_cv"></canvas>
         <canvas id="reg_cv_tip" width="100" height="25" style="background-color:#ffffffa0;border:1px solid blue;position:absolute;left:-200px;top:100px;"></canvas>
      </div>
      <?php
   }
 

   //! Output data array.
   /*! This method outputs the data as a JS-formatted array.
    */
   function data()
   {
      print "var reg_data0_ = [\n";
      for ($i = 0, $n = $this->start_; $i < $this->cnt_; $i++, $n++)
      {
         $j = $n + 1;

         $t = "#{$j}";
         $t .= isset($this->desc_[$n]) ? ': ' . preg_replace('/\'/', '\\\'', $this->desc_[$n]['title']) : '';
         $t .= "\\nDownloads: {$this->reg_->val()[$n]}";

         print "{n: {$j}, val: {$this->reg_->val()[$n]}, rval: {$this->reg_->rval()[$n]}, dev: {$this->reg_->dev()[$n]}, p: {$this->reg_->p()[$n]}, px: '{$this->px($n)}', tx: '$t'},\n";
      }
      print "];\n";

      print "var reg_parm0_ = {b0: {$this->reg_->rparam()['b0']}, b1: {$this->reg_->rparam()['b1']}, r2: {$this->reg_->rparam()['r2']}, max: {$this->reg_->max()}, ystep: {$this->ystep_}};\n";
   }


   //! Output JS code.
   /*! This function reads the JS code from the source file and outputs it.
    */
   function code()
   {
      if (($jsc = file_get_contents(dirname(__FILE__) . '/regression.js')) === FALSE)
         return -1;

      print $jsc;
      return 0;
   }


   //! Output full HTML code.
   /*! This method outputs all necessary HTML and JS code.
    */
   function output()
   {
      $this->html();
      print "<script>\n";
      $this->data();
      $this->code();
      print "</script>\n";
   }
}


/*! This class is Podlove-related and generates the regression of the episodes
   * and outputs the necessery HTML and JS code to be displayed within the
   * podlove analytics page.
 */
class PodloveRegression
{
   //! internal array to keep the downloads of all episodes
   protected $data_ = array();
   //! internal array to keep descriptional data of episodes
   protected $desc_ = array();


   function __construct()
   {
      $this->get_episode_data();
      $this->regression();
   }


   /*! Retrieve the download values of each episode after 2 weeks of
    * publishing.
    */
   function get_episode_data()
   {
      $episodes = Model\Episode::all();
      foreach ( $episodes as $episode )
      {
         $post_id = $episode->post_id;
         $post = get_post($post_id);

         // skip deleted podcasts
         if (!in_array($post->post_status, array('pending', 'draft', 'publish', 'future')))
            continue;

         // skip versions
         if ($post->post_type != 'podcast')
            continue;

         // break loop if there is no data
         if (!($d = get_post_meta($post->ID, '_podlove_downloads_2w', true)))
            break;

         $this->data_[] = $d;
         $this->desc_[] = array('title' => $post->post_title);
      }
   }


   /*! Output regression code.
    */
   function regression()
   {
      // initializing HTML code
      ?>
      <div class="metabox-holder">
      <div class="postbox">
      <h2 class="hndle" style="cursor: inherit;">Regression</h2>
      <div class="inside">
      <?php

      // calculate regression
      $reg = new Regression($this->data_);
      $reg->reg_calc();

      // generate regression HTML/JS code
      $jsr = new JSRegression($reg, 25);
      $jsr->set_description($this->desc_);
      // output code
      $jsr->output();

      // HTML closing tags
      ?>
      </div>
      </div>
      </div>
      <?php
   }
}


/*! This is a test class and is only used for development. If instantiated, it
   * generates a full HTML document containing some test data.
 */
class TestRegression
{
/*   protected $data_ = array(52, 88, 90, 94, 146, 190, 174, 130, 230, 270, 136,
      116, 714, 406, 224, 260, 376, 366, 258, 374, 328, 492, 508, 528, 870,
      576, 716, 494, 528, 446, 406, 518, 494, 584, 484, 612);
 */
   protected $data_ = array(3, 5, 7, 9, 11, 13, 15, 19);


   function __construct()
   {
      // calculate regression
      $reg = new Regression($this->data_);
      $reg->reg_calc();

      $jsr = new JSRegression($reg, 25);
      ?><!DOCTYPE html>
      <html>
      <head> <meta charset="utf-8"/> </head>
      <body>

      <?php $jsr->output(); ?>

      <pre>
      <?php print_r($reg); ?>
      </pre>
      </body>
      </html>
      <?php

   }
}

?>
