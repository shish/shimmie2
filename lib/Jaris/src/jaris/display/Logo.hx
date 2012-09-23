/**    
 * @author Jefferson González
 * @copyright 2010 Jefferson González
 *
 * @license 
 * This file is part of Jaris FLV Player.
 *
 * Jaris FLV Player is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License or GNU LESSER GENERAL 
 * PUBLIC LICENSE as published by the Free Software Foundation, either version 
 * 3 of the License, or (at your option) any later version.
 *
 * Jaris FLV Player is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License and 
 * GNU LESSER GENERAL PUBLIC LICENSE along with Jaris FLV Player.  If not, 
 * see <http://www.gnu.org/licenses/>.
 */

package jaris.display;

import flash.display.Loader;
import flash.display.MovieClip;
import flash.display.Sprite;
import flash.display.Stage;
import flash.events.Event;
import flash.events.IOErrorEvent;
import flash.events.MouseEvent;
import flash.Lib;
import flash.net.URLRequest;

/**
 * To display an image in jpg, png or gif format as logo
 */
class Logo extends Sprite
{
	private var _stage:Stage;
	private var _movieClip:MovieClip;
	private var _loader:Loader;
	private var _position:String;
	private var _alpha:Float;
	private var _source:String;
	private var _width:Float;
	private var _link:String;
	private var _loading:Bool;
	
	public function new(source:String, position:String, alpha:Float, width:Float=0.0) 
	{
		super();
		
		_stage = Lib.current.stage;
		_movieClip = Lib.current;
		_loader = new Loader();
		_position = position;
		_alpha = alpha;
		_source = source;
		_width = width;
		_loading = true;
		
		this.tabEnabled = false;
		
		_loader.contentLoaderInfo.addEventListener(Event.COMPLETE, onLoaderComplete);
		_loader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, onNotLoaded);
        _loader.load(new URLRequest(source));
	}
	
	/**
	 * Triggers when the logo image could not be loaded
	 * @param	event
	 */
	private function onNotLoaded(event:IOErrorEvent):Void
	{
		//Image not loaded
	}
	
	/**
	 * Triggers when the logo image finished loading.
	 * @param	event
	 */
	private function onLoaderComplete(event:Event):Void
	{
		addChild(_loader);
		
		setWidth(_width);
		setPosition(_position);
		setAlpha(_alpha);
		_loading = false;
		
		_stage.addEventListener(Event.RESIZE, onStageResize);
	}
	
	/**
	 * Recalculate logo position on stage resize
	 * @param	event
	 */
	private function onStageResize(event:Event):Void
	{
		setPosition(_position);
	}
	
	/**
	 * Opens the an url when the logo is clicked
	 * @param	event
	 */
	private function onLogoClick(event:MouseEvent):Void
	{
		Lib.getURL(new URLRequest(_link), "_blank");
	}
	
	/**
	 * Position where logo will be showing
	 * @param	position values could be top left, top right, bottom left, bottom right
	 */
	public function setPosition(position:String):Void
	{
		switch(position)
		{
			case "top left":
				this.x = 25;
				this.y = 25;
			
			case "top right":
				this.x = _stage.stageWidth - this._width - 25;
				this.y = 25;
			
			case "bottom left":
				this.x = 25;
				this.y = _stage.stageHeight - this.height - 25;
			
			case "bottom right":
				this.x = _stage.stageWidth - this.width - 25;
				this.y = _stage.stageHeight - this.height - 25;
				
			default: 
				this.x = 25;
				this.y = 25;
		}
	}
	
	/**
	 * To set logo transparency
	 * @param	alpha
	 */
	public function setAlpha(alpha:Float):Void
	{
		this.alpha = alpha;
	}
	
	/**
	 * Sets logo width and recalculates height keeping aspect ratio
	 * @param	width
	 */
	public function setWidth(width:Float):Void
	{
		if (width > 0)
		{
			this.height = (this.height / this.width) * width;
			this.width = width;
		}
	}
	
	/**
	 * Link that opens when clicked the logo image is clicked
	 * @param	link
	 */
	public function setLink(link:String):Void
	{
		_link = link;
		this.buttonMode = true;
		this.useHandCursor = true;
		this.addEventListener(MouseEvent.CLICK, onLogoClick);
	}
	
	/**
	 * To check if the logo stills loading
	 * @return true if loading false otherwise
	 */
	public function isLoading():Bool
	{
		return _loading;
	}
}