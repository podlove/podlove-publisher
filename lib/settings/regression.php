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
* @auther Bernhard R. Fischer
* @date 2019/11/30
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

      $xy_sum = 0;
      $xx_sq = 0;
      for ($i = 0; $i < $this->cnt_; $i++)
      {
         $xix = $i - $avg_ep;
         $yiy = $this->dat_['val'][$i] - $avg_dn;
         $xy_sum += $xix * $yiy;
         $xx_sq += $xix * $xix;
      }

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
         $this->dat_['dev'][$i] = $this->dat_['val'][$i] - $this->dat_['rval'][$i];
         if ($this->dat_['rval'][$i])
            $this->dat_['p'][$i] = $this->dat_['dev'][$i] / $this->dat_['rval'][$i];
      }

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
}


/*! This class is a helper class the generate JS Canvas code from PHP.
 */
class JSCanvas
{
   protected $id_;
   protected $width_;
   protected $height_;

   protected $script_;


   function __construct($id, $width = 800, $height = 200)
   {
      $this->id_ = $id;
      $this->width_ = $width;
      $this->height_ = $height;
   }


   function output_canvas_tag()
   {
      echo("<canvas id=\"$this->id_\" width=\"$this->width_\" height=\"$this->height_\"></canvas>\n");
   }


   function output_script()
   {
      echo($this->script_);
   }


   function start_script()
   {
      $this->script_ .= "<script>\n";
      $this->script_ .= "$this->id_();\nfunction $this->id_()\n{\nvar canvas = document.getElementById('$this->id_');\nif (canvas.getContext)\n{\nvar context = canvas.getContext('2d');\n";
   }


   function end_script()
   {
      $this->script_ .= "}\n}\n";
      $this->script_ .= "</script>\n";
   }


   function beginPath()
   {
      $this->script_ .= "context.beginPath();\n";
   }


   function moveTo($x, $y)
   {
      $this->script_ .= "context.moveTo($x, $y);\n";
   }


   function lineTo($x, $y)
   {
      $this->script_ .= "context.lineTo($x, $y);\n";
   }


   function stroke()
   {
      $this->script_ .= "context.stroke();\n";
   }


   function fill()
   {
      $this->script_ .= "context.fill();\n";
   }


   function rect($x1, $y1, $x2, $y2)
   {
      $this->beginPath();
      $this->script_ .= "context.rect($x1, $y1, $x2, $y2);\n";
   }


   function strokeStyle($s)
   {
      $this->script_ .= "context.strokeStyle = '$s';\n";
   }


   function fillStyle($s)
   {
      $this->script_ .= "context.fillStyle = '$s';\n";
   }


   function lineWidth($w)
   {
      $this->script_ .= "context.lineWidth = $w;\n";
   }


   function fillText($t, $x, $y)
   {
      $this->script_ .= "context.fillText('$t', $x, $y);\n";
   }
   
   
   function setLineDash($a)
   {
      $this->script_ .= "context.setLineDash([";
      for ($i = 0; $i < count($a); $i++)
      {
         if ($i)
            $this->script_ .= ", ";
         $this->script_ .= $a[$i];
      }
      $this->script_ .= "]);\n";
   }
}


/*! This class creates the HTML canvas and the necessary JS code to generate a
   * regression diagram.
 */
class DrawRegression
{
   protected $reg_;
   protected $can_;

   protected $width_;
   protected $height_;

   protected $border_ = 20;
   protected $bars_ = 5;
   protected $xmul_ = 1;
   protected $ymul_ = 1;


   function __construct($reg, $width = 800, $height = 200)
   {
      $this->reg_ = $reg;
      $this->width_ = $width;
      $this->height_ = $height;

      $this->can_ = new JSCanvas("reg_can", $width, $height);
      $this->set_scale_factors();
   }


   function __destruct()
   {
      unset($this->can_);
   }


   protected function set_scale_factors()
   {
      if ($this->reg_->count())
         $this->xmul_ = ($this->width_ - 2 * $this->border_) / $this->reg_->count();
      if ($this->reg_->max())
         $this->ymul_ = ($this->height_ - 2 * $this->border_) / $this->reg_->max();
   }


   function set_border($border)
   {
      $this->border_ = $border;
      $this->set_scale_factors();
   }


   function draw_regline()
   {
      $this->can_->beginPath();
      $this->can_->moveTo(round($this->border_ + $this->xmul_), round($this->height_ - $this->border_ - ($this->reg_->rval()[0] * $this->ymul_)));
      $this->can_->lineTo(round($this->width_ - $this->border_), round($this->height_ - $this->border_ - ($this->reg_->rval()[$this->reg_->count() - 1] * $this->ymul_)));
      $this->can_->strokeStyle("#ffd700b0");
      $this->can_->lineWidth($this->bars_);
      $this->can_->stroke();
   }


   function draw_bars()
   {
      $this->can_->fillStyle("#6a5acde0");
      $bars = $this->xmul_ * 0.5;
      for ($i = 0; $i < $this->reg_->count(); $i++)
      {
         $this->can_->rect(round($this->border_ + ($i + 1) * $this->xmul_ - $bars / 2),
            round($this->height_ - $this->border_ - $this->reg_->val()[$i] * $this->ymul_),
            round($bars), round($this->reg_->val()[$i] * $this->ymul_));
         $this->can_->fill();
      }
   }


   function draw_trend()
   {
      $this->can_->fillStyle("#6a5acda0");
      $bars = $this->xmul_ * 0.2;
      for ($i = 0; $i < $this->reg_->count(); $i++)
      {
         $this->can_->fillStyle($this->reg_->dev()[$i] >= 0 ? "#98fb98" : "#fa8072a0");
         $this->can_->rect(round($this->border_ + ($i + 1) * $this->xmul_ - $bars / 2),
            round($this->height_ - $this->border_ - $this->reg_->dev()[$i] * $this->ymul_),
            round($bars), round($this->reg_->dev()[$i] * $this->ymul_));
         $this->can_->fill();

      }
   }


   function draw_ptext()
   {
      $this->can_->fillStyle("#202020");
      for ($i = 0; $i < $this->reg_->count(); $i++)
      {
         $p = round($this->reg_->p()[$i] * 100, 1);
         if (abs($p) >= 9.5)
            $p = round($p);

         $this->can_->fillText($p >= 0 ? "+$p%" : "$p%",
            round($this->border_ + ($i + .5) * $this->xmul_),
            round($this->height_ - $this->border_ - $this->reg_->val()[$i] * $this->ymul_));
      }
   }


   private function yscale()
   {
      $ys = 5;

      for ($m = $this->reg_->max(); $m / $ys > 15; $ys *= 2);

      return $ys;
   }


   function draw_axis()
   {
      $this->can_->strokeStyle("#303030");
      $this->can_->lineWidth(1);

      $this->can_->beginPath();
      $this->can_->moveTo(round($this->border_), $this->height_ - $this->border_);
      $this->can_->lineTo(round($this->width_ - $this->border_ + $this->xmul_ * 0.5), $this->height_ - $this->border_);
      $this->can_->stroke();

      $this->can_->beginPath();
      $this->can_->moveTo(round($this->border_), $this->height_ - $this->border_);
      $this->can_->lineTo(round($this->border_), $this->border_);
      $this->can_->stroke();

      for ($i = 0; $i < $this->reg_->count(); $i++)
      {
         $ii = $i + 1;
         $this->can_->fillText($ii, round($this->border_ + ($i + 1) * $this->xmul_) . "-context.measureText('$ii').width/2", round($this->height_ - $this->border_ * 0.5 ));
      }

      $this->can_->strokeStyle("#202020");
      $this->can_->setLineDash(array(8, 2));
      $ys = $this->yscale();
      for ($i = $ys; $i < $this->reg_->max(); $i += $ys)
      {
         $this->can_->beginPath();
         $this->can_->moveTo(round($this->border_), round($this->height_ - $this->border_ - $i * $this->ymul_));
         $this->can_->lineTo(round($this->width_ - $this->border_ + $this->xmul_ * .5), round($this->height_ - $this->border_ - $i * $this->ymul_));
         $this->can_->stroke();

         $this->can_->fillText($i, $this->border_ . "-context.measureText('$i ').width", round($this->height_ - $this->border_ - $i * $this->ymul_));
      }
      $this->can_->setLineDash(array());
   }


   function draw()
   {

      $this->can_->start_script();

      $this->draw_axis();
      $this->draw_bars();
      $this->draw_regline();
      $this->draw_trend();
      $this->draw_ptext();

      $this->can_->end_script();
   }


   function output()
   {
      $this->can_->output_canvas_tag();
      $this->can_->output_script();
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

         $releaseDate = new \DateTime($post->post_date);
         $releaseDate->setTime(0, 0, 0);

         $d2 = new \DateTime($post->post_date);
         $d2->add(new \DateInterval("P14D"));

         $this->data_[] = Model\DownloadIntentClean::total_by_episode_id($episode->id, $releaseDate->format("Y-m-d"), $d2->format("Y-m-d"));
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
      $dctx = new DrawRegression($reg, 1024, 256);
      $dctx->draw();
      // output code
      $dctx->output();
 
      // HTML closing tags
      ?>
      </div>
      </div>
      </div>
      <?php
   }
}

?>
