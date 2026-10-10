/**
 * Section icons: one outline set (24 grid, 2px rounded strokes), so the titles read as a family
 *
 * The SVGs live in assets/icons/ (svgr: ReactComponent) and are shared with PHP (the Grid Layouts list badges, Admin::icon()).
 * fill is set on every shape in the files: panel styles paint each svg with fill.
 */
import { ReactComponent as Shortcodes } from '../../icons/shortcodes.svg';
import { ReactComponent as Content } from '../../icons/content.svg';
import { ReactComponent as Order } from '../../icons/order.svg';
import { ReactComponent as Layout } from '../../icons/layout.svg';
import { ReactComponent as Skin } from '../../icons/skin.svg';
import { ReactComponent as Filter } from '../../icons/filter.svg';
import { ReactComponent as Pagination } from '../../icons/pagination.svg';
import { ReactComponent as Animation } from '../../icons/animation.svg';
import { ReactComponent as Inserts } from '../../icons/inserts.svg';
import { ReactComponent as Css } from '../../icons/css.svg';

export const shortcodes = <Shortcodes />;
export const content = <Content />;
export const order = <Order />;
export const layout = <Layout />;
export const skin = <Skin />;
export const filter = <Filter />;
export const pagination = <Pagination />;
export const animation = <Animation />;
export const inserts = <Inserts />;
export const css = <Css />;
