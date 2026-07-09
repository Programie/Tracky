declare module "*.scss";
declare module "*.css";

declare module "*.png" {
    const src: string;
    export default src;
}

declare module "*.jpg" {
    const src: string;
    export default src;
}

declare module "*.svg" {
    const src: string;
    export default src;
}
