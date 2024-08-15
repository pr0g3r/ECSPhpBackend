{
  description = "Customer Services flake with a shell enviroment for development";
  inputs.nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";
  inputs.flake-utils.url = "github:numtide/flake-utils";

  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
      in
      {
        devShell = pkgs.mkShell {
          nativeBuildInputs = [ pkgs.bashInteractive ];
          buildInputs = [
            pkgs.python39Packages.django
            pkgs.python39Packages.djangorestframework
            pkgs.python39Packages.django-filter
            pkgs.python39Packages.django-cors-headers
            pkgs.python39Packages.djangorestframework-simplejwt
            pkgs.python-language-server
          ];
        };
      });
}
